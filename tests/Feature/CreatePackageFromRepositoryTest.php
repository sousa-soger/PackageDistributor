<?php

use App\Jobs\GenerateDeploymentPackageJob;
use App\Models\DeploymentJob;
use App\Models\Repository;
use App\Models\User;
use App\Services\DeploymentPackageService;
use App\Services\OAuthTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function packageRepositoryFor(User $owner, array $attributes = []): Repository
{
    return $owner->repositories()->create(array_merge([
        'branches' => ['main', 'release'],
        'default_branch' => 'main',
        'display_name' => 'Package Source',
        'name' => 'acme/package-source',
        'provider' => 'github',
        'status' => 'connected',
        'tags' => ['v1.0.0', 'v1.1.0'],
        'url' => 'https://github.com/acme/package-source',
    ], $attributes));
}

class CreatePackageRepositoryCapturingService extends DeploymentPackageService
{
    public array $call = [];

    public function generate(
        string $environment,
        string $projectName,
        string $baseVersion,
        string $headVersion,
        string $repo,
        string $packageName,
        callable $progressCallback,
        string $vcsProvider = 'github',
        string $vcsToken = ''
    ): array {
        $this->call = compact(
            'environment',
            'projectName',
            'baseVersion',
            'headVersion',
            'repo',
            'packageName',
            'vcsProvider',
            'vcsToken'
        );

        $progressCallback(['packagingProgress' => 100], 'Done.');

        return [
            'folder_name' => $packageName,
            'message' => 'Package created successfully.',
            'status' => 'success',
            'summary' => ['total_changes' => 0],
        ];
    }
}

test('create package page lists owned and invited repositories with creator access', function () {
    $actor = User::factory()->create();
    $owner = User::factory()->create();

    packageRepositoryFor($actor, ['display_name' => 'Owned App', 'name' => 'acme/owned-app']);
    $creatorRepository = packageRepositoryFor($owner, ['display_name' => 'Invited App', 'name' => 'acme/invited-app']);
    $viewerRepository = packageRepositoryFor($owner, ['display_name' => 'Viewer App', 'name' => 'acme/viewer-app']);

    $creatorRepository->members()->attach($actor->id, ['role' => 'creator', 'source' => 'ldap']);
    $viewerRepository->members()->attach($actor->id, ['role' => 'viewer', 'source' => 'ldap']);

    $this->actingAs($actor)
        ->get(route('create-package', ['repository' => $creatorRepository->id]))
        ->assertOk()
        ->assertSee('Owned App')
        ->assertSee('Invited App')
        ->assertDontSee('Viewer App');
});

test('create package page lists connected local repositories', function () {
    $actor = User::factory()->create();
    $storagePath = storage_path('app/repos/local-app.git');
    File::ensureDirectoryExists($storagePath);

    try {
        packageRepositoryFor($actor, [
            'display_name' => 'Uploaded Local App',
            'name' => 'uploaded-local-app',
            'provider' => 'local-pc',
            'storage_path' => $storagePath,
            'type' => 'uploaded',
            'url' => 'repository.zip',
        ]);

        $this->actingAs($actor)
            ->get(route('create-package'))
            ->assertOk()
            ->assertSee('Uploaded Local App')
            ->assertSee('Local Repository');
    } finally {
        File::deleteDirectory($storagePath);
    }
});

test('connected local repository can queue a package', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $storagePath = storage_path('app/repos/local-queue-test.git');
    File::ensureDirectoryExists($storagePath);

    try {
        $repository = packageRepositoryFor($owner, [
            'display_name' => 'Local Queue Repo',
            'name' => 'local-queue-repo',
            'provider' => 'local-pc',
            'storage_path' => $storagePath,
            'type' => 'uploaded',
            'url' => 'local.zip',
        ]);

        $this->actingAs($owner)
            ->postJson(route('deployments.queue-job'), [
                'base_version' => 'main',
                'environment' => 'DEV',
                'head_version' => 'release',
                'repository_id' => $repository->id,
                'vcs_provider' => 'local-pc',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'queued');

        $job = DeploymentJob::firstOrFail();

        expect($job->repository_id)->toBe($repository->id)
            ->and($job->repo)->toBe($storagePath)
            ->and($job->vcs_provider)->toBe('local-pc')
            ->and($job->project_name)->toBe('Local Queue Repo');

        Queue::assertPushed(GenerateDeploymentPackageJob::class);
    } finally {
        File::deleteDirectory($storagePath);
    }
});

test('gitless archives can queue a one time package', function () {
    Queue::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('deployments.queue-gitless-job'), [
            'base_archive' => UploadedFile::fake()->create('base.zip', 1, 'application/zip'),
            'environment' => 'DEV',
            'head_archive' => UploadedFile::fake()->create('target.zip', 1, 'application/zip'),
            'package_name' => 'DEV-gitless-test',
            'project_name' => 'Gitless folders',
        ], [
            'Accept' => 'application/json',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'queued');

    $job = DeploymentJob::firstOrFail();

    try {
        expect($job->user_id)->toBe($user->id)
            ->and($job->repository_id)->toBeNull()
            ->and($job->vcs_provider)->toBe('gitless')
            ->and($job->project_name)->toBe('Gitless folders')
            ->and(File::isDirectory($job->repo))->toBeTrue()
            ->and(File::isFile($job->repo.DIRECTORY_SEPARATOR.'base.zip'))->toBeTrue()
            ->and(File::isFile($job->repo.DIRECTORY_SEPARATOR.'head.zip'))->toBeTrue();

        Queue::assertPushed(GenerateDeploymentPackageJob::class);
    } finally {
        File::deleteDirectory($job->repo);
    }
});

test('gitless package auto name uses different archive names', function () {
    Queue::fake();
    Carbon::setTestNow(Carbon::parse('2026-05-13 14:05:00'));

    $user = User::factory()->create();
    $job = null;

    try {
        $this->actingAs($user)
            ->post(route('deployments.queue-gitless-job'), [
                'base_archive' => UploadedFile::fake()->create('website-base.zip', 1, 'application/zip'),
                'environment' => 'QA',
                'head_archive' => UploadedFile::fake()->create('website-target.zip', 1, 'application/zip'),
                'project_name' => 'Gitless folders',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('package_name', 'QA-website-base-to-website-target-260513-1405');

        $job = DeploymentJob::firstOrFail();

        expect($job->package_name)->toBe('QA-website-base-to-website-target-260513-1405');
    } finally {
        Carbon::setTestNow();
        if ($job) {
            File::deleteDirectory($job->repo);
        }
    }
});

test('gitless package auto name uses one archive name when both names match', function () {
    Queue::fake();
    Carbon::setTestNow(Carbon::parse('2026-05-13 14:05:00'));

    $user = User::factory()->create();
    $job = null;

    try {
        $this->actingAs($user)
            ->post(route('deployments.queue-gitless-job'), [
                'base_archive' => UploadedFile::fake()->create('portal.zip', 1, 'application/zip'),
                'environment' => 'DEV',
                'head_archive' => UploadedFile::fake()->create('portal.zip', 1, 'application/zip'),
                'project_name' => 'Gitless folders',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('package_name', 'DEV-portal-260513-1405');

        $job = DeploymentJob::firstOrFail();

        expect($job->package_name)->toBe('DEV-portal-260513-1405');
    } finally {
        Carbon::setTestNow();
        if ($job) {
            File::deleteDirectory($job->repo);
        }
    }
});

test('invited package creator can queue a package for a connected repository', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $creator = User::factory()->create();
    $repository = packageRepositoryFor($owner, [
        'display_name' => 'Owner Credentials Repo',
        'name' => 'acme/private-repo',
    ]);

    $repository->members()->attach($creator->id, ['role' => 'creator', 'source' => 'ldap']);

    $this->actingAs($creator)
        ->postJson(route('deployments.queue-job'), [
            'base_version' => 'v1.0.0',
            'environment' => 'DEV',
            'head_version' => 'v1.1.0',
            'repository_id' => $repository->id,
        ])
        ->assertOk()
        ->assertJsonPath('status', 'queued');

    $job = DeploymentJob::firstOrFail();

    expect($job->user_id)->toBe($creator->id)
        ->and($job->repository_id)->toBe($repository->id)
        ->and($job->repo)->toBe('acme/private-repo')
        ->and($job->vcs_provider)->toBe('github')
        ->and($job->project_name)->toBe('Owner Credentials Repo');

    Queue::assertPushed(GenerateDeploymentPackageJob::class);
});

test('repository package auto name uses two digit year timestamp', function () {
    Queue::fake();
    Carbon::setTestNow(Carbon::parse('2026-05-13 14:05:00'));

    $owner = User::factory()->create();
    $repository = packageRepositoryFor($owner, [
        'display_name' => 'Package Source',
        'name' => 'acme/package-source',
    ]);

    try {
        $this->actingAs($owner)
            ->postJson(route('deployments.queue-job'), [
                'base_version' => 'v1.0.0',
                'environment' => 'PROD',
                'head_version' => 'v1.1.0',
                'repository_id' => $repository->id,
            ])
            ->assertOk()
            ->assertJsonPath('package_name', 'PROD-Package Source-v1.0.0-to-v1.1.0-260513-1405');

        expect(DeploymentJob::firstOrFail()->package_name)
            ->toBe('PROD-Package Source-v1.0.0-to-v1.1.0-260513-1405');
    } finally {
        Carbon::setTestNow();
    }
});

test('repository maintainer can queue and deploy packages for a connected repository', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $maintainer = User::factory()->create();
    $repository = packageRepositoryFor($owner, [
        'display_name' => 'Maintained Repo',
        'name' => 'acme/maintained-repo',
    ]);

    $repository->members()->attach($maintainer->id, ['role' => 'maintainer', 'source' => 'ldap']);

    $this->actingAs($maintainer)
        ->postJson(route('deployments.queue-job'), [
            'base_version' => 'v1.0.0',
            'environment' => 'DEV',
            'head_version' => 'v1.1.0',
            'repository_id' => $repository->id,
        ])
        ->assertOk()
        ->assertJsonPath('status', 'queued');

    $job = DeploymentJob::firstOrFail();

    expect($maintainer->can('deployPackage', $repository))->toBeTrue()
        ->and($maintainer->can('deploy', $job))->toBeTrue()
        ->and($job->user_id)->toBe($maintainer->id)
        ->and($job->repository_id)->toBe($repository->id);

    Queue::assertPushed(GenerateDeploymentPackageJob::class);
});

test('repository viewer cannot queue a package', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $repository = packageRepositoryFor($owner);

    $repository->members()->attach($viewer->id, ['role' => 'viewer', 'source' => 'ldap']);

    $this->actingAs($viewer)
        ->postJson(route('deployments.queue-job'), [
            'base_version' => 'v1.0.0',
            'environment' => 'DEV',
            'head_version' => 'v1.1.0',
            'repository_id' => $repository->id,
        ])
        ->assertForbidden();

    expect(DeploymentJob::count())->toBe(0);
});

test('repository owner can delete packages created by repository members', function () {
    $owner = User::factory()->create();
    $creator = User::factory()->create();
    $repository = packageRepositoryFor($owner);
    $repository->members()->attach($creator->id, ['role' => 'creator', 'source' => 'ldap']);
    $job = DeploymentJob::create([
        'base_version' => 'v1.0.0',
        'environment' => 'DEV',
        'head_version' => 'v1.1.0',
        'package_name' => 'DEV-owner-can-delete',
        'project_name' => 'Package Source',
        'repo' => $repository->name,
        'repository_id' => $repository->id,
        'status' => 'completed',
        'user_id' => $creator->id,
        'vcs_provider' => 'github',
    ]);

    $this->actingAs($owner)
        ->deleteJson(route('deployments.bulk-delete'), ['ids' => [$job->id]])
        ->assertOk();

    $this->assertDatabaseMissing('deployment_jobs', ['id' => $job->id]);
});

test('repository maintainer can delete packages created by anyone in the repository', function () {
    $owner = User::factory()->create();
    $maintainer = User::factory()->create();
    $creator = User::factory()->create();
    $repository = packageRepositoryFor($owner);
    $repository->members()->attach($maintainer->id, ['role' => 'maintainer', 'source' => 'ldap']);
    $repository->members()->attach($creator->id, ['role' => 'creator', 'source' => 'ldap']);
    $job = DeploymentJob::create([
        'base_version' => 'v1.0.0',
        'environment' => 'DEV',
        'head_version' => 'v1.1.0',
        'package_name' => 'DEV-maintainer-can-delete',
        'project_name' => 'Package Source',
        'repo' => $repository->name,
        'repository_id' => $repository->id,
        'status' => 'completed',
        'user_id' => $creator->id,
        'vcs_provider' => 'github',
    ]);

    $this->actingAs($maintainer)
        ->deleteJson(route('deployments.bulk-delete'), ['ids' => [$job->id]])
        ->assertOk();

    $this->assertDatabaseMissing('deployment_jobs', ['id' => $job->id]);
});

test('package creator can delete only packages they created', function () {
    $owner = User::factory()->create();
    $creator = User::factory()->create();
    $otherCreator = User::factory()->create();
    $repository = packageRepositoryFor($owner);
    $repository->members()->attach($creator->id, ['role' => 'creator', 'source' => 'ldap']);
    $repository->members()->attach($otherCreator->id, ['role' => 'creator', 'source' => 'ldap']);
    $ownJob = DeploymentJob::create([
        'base_version' => 'v1.0.0',
        'environment' => 'DEV',
        'head_version' => 'v1.1.0',
        'package_name' => 'DEV-creator-own',
        'project_name' => 'Package Source',
        'repo' => $repository->name,
        'repository_id' => $repository->id,
        'status' => 'completed',
        'user_id' => $creator->id,
        'vcs_provider' => 'github',
    ]);
    $otherJob = DeploymentJob::create([
        'base_version' => 'v1.0.0',
        'environment' => 'DEV',
        'head_version' => 'v1.1.0',
        'package_name' => 'DEV-creator-other',
        'project_name' => 'Package Source',
        'repo' => $repository->name,
        'repository_id' => $repository->id,
        'status' => 'completed',
        'user_id' => $otherCreator->id,
        'vcs_provider' => 'github',
    ]);

    $this->actingAs($creator)
        ->deleteJson(route('deployments.bulk-delete'), ['ids' => [$otherJob->id]])
        ->assertForbidden();

    $this->assertDatabaseHas('deployment_jobs', ['id' => $otherJob->id]);

    $this->actingAs($creator)
        ->deleteJson(route('deployments.bulk-delete'), ['ids' => [$ownJob->id]])
        ->assertOk();

    $this->assertDatabaseMissing('deployment_jobs', ['id' => $ownJob->id]);
});

test('packages page renders row zip download action', function () {
    $owner = User::factory()->create();
    $repository = packageRepositoryFor($owner);
    $job = DeploymentJob::create([
        'base_version' => 'v1.0.0',
        'environment' => 'DEV',
        'head_version' => 'v1.1.0',
        'package_name' => 'DEV-row-zip-action',
        'project_name' => 'Package Source',
        'repo' => $repository->name,
        'repository_id' => $repository->id,
        'status' => 'completed',
        'user_id' => $owner->id,
        'vcs_provider' => 'github',
        'zip_size' => '1.2 MB',
    ]);

    $this->actingAs($owner)
        ->get(route('packages.index'))
        ->assertOk()
        ->assertSee('Download ZIP')
        ->assertSee(route('download.archive', ['folder' => $job->package_name, 'format' => '.zip']));
});

test('packages page groups all gitless packages together', function () {
    $user = User::factory()->create();

    DeploymentJob::create([
        'base_version' => 'base-folder',
        'environment' => 'DEV',
        'head_version' => 'target-folder',
        'package_name' => 'DEV-gitless-one',
        'project_name' => 'Gitless Project One',
        'repo' => storage_path('app/temp/gitless-one'),
        'status' => 'completed',
        'user_id' => $user->id,
        'vcs_provider' => 'gitless',
    ]);
    DeploymentJob::create([
        'base_version' => 'base-folder',
        'environment' => 'QA',
        'head_version' => 'target-folder',
        'package_name' => 'QA-gitless-two',
        'project_name' => 'Gitless Project Two',
        'repo' => storage_path('app/temp/gitless-two'),
        'status' => 'completed',
        'user_id' => $user->id,
        'vcs_provider' => 'gitless',
    ]);

    $content = $this->actingAs($user)
        ->get(route('packages.index'))
        ->assertOk()
        ->assertSee('DEV-gitless-one')
        ->assertSee('QA-gitless-two')
        ->content();

    expect(substr_count($content, '<option value="gitless">Gitless packages</option>'))->toBe(1)
        ->and(substr_count($content, '<div class="truncate font-mono text-sm">Gitless packages</div>'))->toBe(1);
});

test('queued repository package job uses repository owner pat', function () {
    $owner = User::factory()->create();
    $creator = User::factory()->create();
    $repository = packageRepositoryFor($owner, [
        'access_token' => 'owner-pat-token',
        'name' => 'acme/pat-repo',
    ]);
    $repository->members()->attach($creator->id, ['role' => 'creator', 'source' => 'ldap']);

    $job = DeploymentJob::create([
        'base_version' => 'v1.0.0',
        'environment' => 'DEV',
        'head_version' => 'v1.1.0',
        'package_name' => 'DEV-acme-pat',
        'project_name' => 'PAT Repo',
        'repo' => $repository->name,
        'repository_id' => $repository->id,
        'status' => 'queued',
        'user_id' => $creator->id,
        'vcs_provider' => 'github',
    ]);

    $service = new CreatePackageRepositoryCapturingService;
    $oauthTokens = Mockery::mock(OAuthTokenService::class);
    $oauthTokens->shouldNotReceive('accessToken');

    (new GenerateDeploymentPackageJob($job->id))->handle($service, $oauthTokens);

    expect($service->call['repo'])->toBe('acme/pat-repo')
        ->and($service->call['vcsProvider'])->toBe('github')
        ->and($service->call['vcsToken'])->toBe('owner-pat-token')
        ->and($job->fresh()->status)->toBe('completed');
});

test('queued repository package job falls back to repository owner oauth', function () {
    $owner = User::factory()->create(['github_token' => 'owner-oauth-token']);
    $creator = User::factory()->create();
    $repository = packageRepositoryFor($owner, ['name' => 'acme/oauth-repo']);
    $repository->members()->attach($creator->id, ['role' => 'creator', 'source' => 'ldap']);

    $job = DeploymentJob::create([
        'base_version' => 'v1.0.0',
        'environment' => 'DEV',
        'head_version' => 'v1.1.0',
        'package_name' => 'DEV-acme-oauth',
        'project_name' => 'OAuth Repo',
        'repo' => $repository->name,
        'repository_id' => $repository->id,
        'status' => 'queued',
        'user_id' => $creator->id,
        'vcs_provider' => 'github',
    ]);

    $service = new CreatePackageRepositoryCapturingService;
    $oauthTokens = Mockery::mock(OAuthTokenService::class);
    $oauthTokens->shouldReceive('accessToken')
        ->once()
        ->with(Mockery::on(fn (User $user) => $user->id === $owner->id), 'github')
        ->andReturn('owner-oauth-token');

    (new GenerateDeploymentPackageJob($job->id))->handle($service, $oauthTokens);

    expect($service->call['repo'])->toBe('acme/oauth-repo')
        ->and($service->call['vcsToken'])->toBe('owner-oauth-token')
        ->and($job->fresh()->status)->toBe('completed');
});

test('queued local repository package job uses stored mirror path without oauth', function () {
    $owner = User::factory()->create(['github_token' => 'unused-token']);
    $repository = packageRepositoryFor($owner, [
        'name' => 'local-package-repo',
        'provider' => 'local-pc',
        'storage_path' => storage_path('app/repos/local-package-repo.git'),
        'type' => 'uploaded',
    ]);

    $job = DeploymentJob::create([
        'base_version' => 'main',
        'environment' => 'DEV',
        'head_version' => 'release',
        'package_name' => 'DEV-local-package',
        'project_name' => 'Local Package Repo',
        'repo' => 'local-package-repo',
        'repository_id' => $repository->id,
        'status' => 'queued',
        'user_id' => $owner->id,
        'vcs_provider' => 'local-pc',
    ]);

    $service = new CreatePackageRepositoryCapturingService;
    $oauthTokens = Mockery::mock(OAuthTokenService::class);
    $oauthTokens->shouldNotReceive('accessToken');

    (new GenerateDeploymentPackageJob($job->id))->handle($service, $oauthTokens);

    expect($service->call['repo'])->toBe($repository->storage_path)
        ->and($service->call['vcsProvider'])->toBe('local-pc')
        ->and($service->call['vcsToken'])->toBe('')
        ->and($job->fresh()->status)->toBe('completed');
});

test('queued gitless package job uses uploaded archive workspace without oauth', function () {
    $user = User::factory()->create(['github_token' => 'unused-token']);
    $workspace = storage_path('app/temp/gitless-job-test');
    File::ensureDirectoryExists($workspace);

    try {
        $job = DeploymentJob::create([
            'base_version' => 'base-folder',
            'environment' => 'DEV',
            'head_version' => 'target-folder',
            'package_name' => 'DEV-gitless-package',
            'project_name' => 'Gitless folders',
            'repo' => $workspace,
            'status' => 'queued',
            'user_id' => $user->id,
            'vcs_provider' => 'gitless',
        ]);

        $service = new CreatePackageRepositoryCapturingService;
        $oauthTokens = Mockery::mock(OAuthTokenService::class);
        $oauthTokens->shouldNotReceive('accessToken');

        (new GenerateDeploymentPackageJob($job->id))->handle($service, $oauthTokens);

        expect($service->call['repo'])->toBe($workspace)
            ->and($service->call['vcsProvider'])->toBe('gitless')
            ->and($service->call['vcsToken'])->toBe('')
            ->and($job->fresh()->status)->toBe('completed');
    } finally {
        File::deleteDirectory($workspace);
    }
});
