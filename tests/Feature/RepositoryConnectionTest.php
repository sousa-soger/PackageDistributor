<?php

use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

test('github oauth redirect requests private repository access with github scope separator', function () {
    config([
        'services.github.client_id' => 'Ov23li00000000000000',
        'services.github.client_secret' => 'github-secret',
        'services.github.redirect' => 'https://app.example.test/github/oauth/callback',
    ]);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('github.oauth.redirect', ['return_to' => 'repositories']));

    $response->assertRedirect();

    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query['scope'])->toBe('repo read:user user:email');
});

test('github app redirect does not request oauth scopes', function () {
    config([
        'services.github.client_id' => 'Iv200000000000000000',
        'services.github.client_secret' => 'github-secret',
        'services.github.redirect' => 'https://app.example.test/github/oauth/callback',
    ]);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('github.oauth.redirect', ['return_to' => 'repositories']));

    $response->assertRedirect();

    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query['scope'] ?? null)->toBe('');
});

test('github oauth repository connection redirects when token is missing private repo scope', function () {
    config([
        'services.github.api_url' => 'https://api.github.test',
        'services.github.client_id' => 'Ov23li00000000000000',
    ]);

    Http::fake([
        'https://api.github.test/repos/sousa-soger/demo-1' => Http::response(
            ['message' => 'Not Found'],
            404,
            [
                'X-Accepted-OAuth-Scopes' => 'repo',
                'X-OAuth-Scopes' => '',
            ]
        ),
        'https://api.github.test/repos/sousa-soger/demo-1/*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $user = User::factory()->create([
        'github_token' => 'github-oauth-token-without-repo-scope',
        'github_refresh_token' => 'github-refresh-token',
        'github_token_expires_at' => now()->addHour(),
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('repositories.store'), [
            'access_token' => null,
            'auth_method' => 'oauth',
            'name' => 'https://github.com/sousa-soger/demo-1',
            'provider' => 'github',
            'url' => 'https://github.com/sousa-soger/demo-1',
        ]);

    $response
        ->assertConflict()
        ->assertJson([
            'requires_oauth' => true,
        ]);

    expect($response->json('message'))->toContain('repo scope');

    $this->assertDatabaseMissing('repositories', [
        'name' => 'sousa-soger/demo-1',
        'provider' => 'github',
        'user_id' => $user->id,
    ]);
});

test('github app repository connection reports missing installation permissions', function () {
    config([
        'services.github.api_url' => 'https://api.github.test',
        'services.github.client_id' => 'Iv200000000000000000',
    ]);

    Http::fake([
        'https://api.github.test/repos/sousa-soger/demo-1' => Http::response(
            ['message' => 'Not Found'],
            404,
            [
                'X-Accepted-OAuth-Scopes' => 'repo',
                'X-OAuth-Scopes' => '',
            ]
        ),
        'https://api.github.test/repos/sousa-soger/demo-1/*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $user = User::factory()->create([
        'github_token' => 'github-app-user-token',
        'github_refresh_token' => 'github-app-refresh-token',
        'github_token_expires_at' => now()->addHour(),
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('repositories.store'), [
            'access_token' => null,
            'auth_method' => 'oauth',
            'name' => 'https://github.com/sousa-soger/demo-1',
            'provider' => 'github',
            'url' => 'https://github.com/sousa-soger/demo-1',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonMissing([
            'requires_oauth' => true,
        ]);

    expect($response->json('message'))
        ->toContain('GitHub App')
        ->toContain('installed');
});

test('local ssh connection validates required fields', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('repositories.connect-ssh'), []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ip', 'name', 'path']);
});

test('local repositories cannot be connected through the legacy path store flow', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('repositories.store'), [
            'name' => 'H:\xampp\htdocs\test-1',
            'provider' => 'local-pc',
            'url' => 'H:\xampp\htdocs\test-1',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['provider']);
});

test('local upload rejects files that are not zip archives or git bundles', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('repositories.upload'), [
            'file' => UploadedFile::fake()->create('repository.txt', 1, 'text/plain'),
            'name' => 'local-repository',
        ], [
            'Accept' => 'application/json',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'Upload a ZIP archive or Git bundle file.',
            'success' => false,
        ]);

    $this->assertDatabaseMissing('repositories', [
        'name' => 'local-repository',
        'provider' => 'local-pc',
        'user_id' => $user->id,
    ]);
});

test('local upload preserves git history when zip includes hidden git directory', function () {
    if (! class_exists(ZipArchive::class)) {
        $this->markTestSkipped('ZipArchive is not available.');
    }

    $gitVersion = new Process(['git', '--version']);
    $gitVersion->setTimeout(30);
    $gitVersion->run();

    if (! $gitVersion->isSuccessful()) {
        $this->markTestSkipped('Git is not available.');
    }

    $user = User::factory()->create();
    $root = storage_path('framework/testing/git-upload-'.uniqid());
    $repoDir = $root.DIRECTORY_SEPARATOR.'repo';
    $zipPath = $root.DIRECTORY_SEPARATOR.'repository.zip';
    $uploadedRepositoryId = null;

    File::ensureDirectoryExists($repoDir);

    $runGit = function (array $command, string $cwd) {
        $process = new Process($command, $cwd);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->fail($process->getErrorOutput() ?: $process->getOutput());
        }
    };

    try {
        File::put($repoDir.DIRECTORY_SEPARATOR.'README.md', '# Demo');

        $runGit(['git', 'init'], $repoDir);
        $runGit(['git', 'checkout', '-b', 'main'], $repoDir);
        $runGit(['git', 'config', 'user.email', 'test@example.test'], $repoDir);
        $runGit(['git', 'config', 'user.name', 'Test User'], $repoDir);
        $runGit(['git', 'add', '.'], $repoDir);
        $runGit(['git', 'commit', '-m', 'Initial commit'], $repoDir);
        $runGit(['git', 'tag', 'v1.0.0'], $repoDir);
        $runGit(['git', 'checkout', '-b', 'release'], $repoDir);

        $zip = new ZipArchive;
        expect($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($repoDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $relativePath = 'repo/'.str_replace('\\', '/', substr($file->getPathname(), strlen($repoDir) + 1));

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }

        $zip->close();

        $response = $this
            ->actingAs($user)
            ->post(route('repositories.upload'), [
                'file' => new UploadedFile($zipPath, 'repository.zip', 'application/zip', null, true),
                'name' => 'local-with-history',
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'warning' => null,
            ]);

        $uploadedRepositoryId = $response->json('repository.id');

        $this->assertDatabaseHas('repositories', [
            'has_git_history' => true,
            'name' => 'local-with-history',
            'provider' => 'local-pc',
            'type' => 'uploaded',
            'user_id' => $user->id,
        ]);

        expect(File::exists(storage_path("app/repos/{$uploadedRepositoryId}.git/config")))->toBeTrue();

        $repository = Repository::findOrFail($uploadedRepositoryId);

        expect($repository->branches)
            ->toContain('main')
            ->toContain('release')
            ->and($repository->tags)
            ->toContain('v1.0.0');
    } finally {
        File::deleteDirectory($root);

        if ($uploadedRepositoryId) {
            File::deleteDirectory(storage_path("app/repos/{$uploadedRepositoryId}.git"));
        }
    }
});

test('repository cards refresh local branch and tag counts from stored git mirror', function () {
    $gitVersion = new Process(['git', '--version']);
    $gitVersion->setTimeout(30);
    $gitVersion->run();

    if (! $gitVersion->isSuccessful()) {
        $this->markTestSkipped('Git is not available.');
    }

    $user = User::factory()->create();
    $root = storage_path('framework/testing/local-card-counts-'.uniqid());
    $workTree = $root.DIRECTORY_SEPARATOR.'work';

    $runGit = function (array $command, string $cwd) {
        $process = new Process($command, $cwd);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->fail($process->getErrorOutput() ?: $process->getOutput());
        }
    };

    try {
        File::ensureDirectoryExists($workTree);
        File::put($workTree.DIRECTORY_SEPARATOR.'README.md', '# Demo');

        $runGit(['git', 'init'], $workTree);
        $runGit(['git', 'checkout', '-b', 'main'], $workTree);
        $runGit(['git', 'config', 'user.email', 'test@example.test'], $workTree);
        $runGit(['git', 'config', 'user.name', 'Test User'], $workTree);
        $runGit(['git', 'add', '.'], $workTree);
        $runGit(['git', 'commit', '-m', 'Initial commit'], $workTree);
        $runGit(['git', 'tag', 'v1.0.0'], $workTree);
        $runGit(['git', 'checkout', '-b', 'release'], $workTree);

        $repository = $user->repositories()->create([
            'branches' => [],
            'default_branch' => 'main',
            'name' => 'local-card-counts',
            'provider' => 'local-pc',
            'status' => 'connected',
            'storage_path' => $workTree.DIRECTORY_SEPARATOR.'.git',
            'tags' => [],
            'type' => 'uploaded',
        ]);

        $this
            ->actingAs($user)
            ->get(route('repositories'))
            ->assertOk();

        $repository->refresh();

        expect($repository->branch_count)->toBe(2)
            ->and($repository->tag_count)->toBe(1);
    } finally {
        File::deleteDirectory($root);
    }
});

test('deleting local repository removes backend mirror storage', function () {
    $user = User::factory()->create();
    $storagePath = storage_path('app/repos/delete-local-test.git');
    File::ensureDirectoryExists($storagePath);
    File::put($storagePath.DIRECTORY_SEPARATOR.'config', '[core]');

    $repository = $user->repositories()->create([
        'branches' => [],
        'default_branch' => 'main',
        'name' => 'delete-local-test',
        'provider' => 'local-pc',
        'status' => 'connected',
        'storage_path' => $storagePath,
        'tags' => [],
        'type' => 'uploaded',
    ]);

    try {
        $this
            ->actingAs($user)
            ->deleteJson(route('repositories.destroy', $repository))
            ->assertOk()
            ->assertJson([
                'message' => 'Repository removed.',
            ]);

        expect(File::isDirectory($storagePath))->toBeFalse();

        $this->assertDatabaseMissing('repositories', [
            'id' => $repository->id,
        ]);
    } finally {
        File::deleteDirectory($storagePath);
    }
});

test('local repository versions are read from stored git mirror', function () {
    $gitVersion = new Process(['git', '--version']);
    $gitVersion->setTimeout(30);
    $gitVersion->run();

    if (! $gitVersion->isSuccessful()) {
        $this->markTestSkipped('Git is not available.');
    }

    $user = User::factory()->create();
    $root = storage_path('framework/testing/local-versions-'.uniqid());
    $workTree = $root.DIRECTORY_SEPARATOR.'work';

    $runGit = function (array $command, string $cwd) {
        $process = new Process($command, $cwd);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->fail($process->getErrorOutput() ?: $process->getOutput());
        }
    };

    try {
        File::ensureDirectoryExists($workTree);
        File::put($workTree.DIRECTORY_SEPARATOR.'README.md', '# Demo');

        $runGit(['git', 'init'], $workTree);
        $runGit(['git', 'checkout', '-b', 'main'], $workTree);
        $runGit(['git', 'config', 'user.email', 'test@example.test'], $workTree);
        $runGit(['git', 'config', 'user.name', 'Test User'], $workTree);
        $runGit(['git', 'add', '.'], $workTree);
        $runGit(['git', 'commit', '-m', 'Initial commit'], $workTree);
        $runGit(['git', 'tag', 'v1.0.0'], $workTree);
        $runGit(['git', 'checkout', '-b', 'release'], $workTree);

        $repository = $user->repositories()->create([
            'branches' => [],
            'default_branch' => 'main',
            'name' => 'local-versioned',
            'provider' => 'local-pc',
            'status' => 'connected',
            'storage_path' => $workTree.DIRECTORY_SEPARATOR.'.git',
            'tags' => [],
            'type' => 'uploaded',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('repositories.versions', $repository));

        $response->assertOk();

        expect(collect($response->json('branches'))->pluck('name')->all())
            ->toContain('main')
            ->toContain('release')
            ->and(collect($response->json('tags'))->pluck('name')->all())
            ->toContain('v1.0.0');
    } finally {
        File::deleteDirectory($root);
    }
});

test('ssh sync endpoint only accepts ssh mirror repositories', function () {
    $user = User::factory()->create();
    $repository = $user->repositories()->create([
        'branches' => [],
        'default_branch' => 'main',
        'name' => 'uploaded-local',
        'provider' => 'local-pc',
        'status' => 'connected',
        'tags' => [],
        'type' => 'uploaded',
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('repositories.sync-ssh', $repository));

    $response
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'This repository is not connected through SSH access.',
            'success' => false,
        ]);
});
