<?php

use App\Jobs\GenerateDeploymentPackageJob;
use App\Models\DeploymentJob;
use App\Models\Repository;
use App\Models\User;
use App\Services\DeploymentPackageService;
use App\Services\OAuthTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
