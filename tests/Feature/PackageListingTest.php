<?php

use App\Models\DeploymentJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('packages page keeps package lookup keyed by deployment job id when ids have gaps', function () {
    $user = User::factory()->create([
        'name' => 'Package Owner',
    ]);

    $repository = $user->repositories()->create([
        'branches' => ['main'],
        'default_branch' => 'main',
        'name' => 'owner/example-repository',
        'provider' => 'github',
        'status' => 'connected',
        'tags' => ['v1.0.0'],
    ]);

    $olderPackage = DeploymentJob::create([
        'user_id' => $user->id,
        'repository_id' => $repository->id,
        'repo' => $repository->name,
        'vcs_provider' => 'github',
        'project_name' => 'example-repository',
        'environment' => 'DEV',
        'base_version' => 'v1.0.0',
        'head_version' => 'v1.1.0',
        'package_name' => 'DEV-example-repository-v1.0.0-to-v1.1.0',
        'status' => 'completed',
        'created_at' => now()->subMinutes(10),
        'updated_at' => now()->subMinutes(10),
    ]);

    $deletedPackage = DeploymentJob::create([
        'user_id' => $user->id,
        'repository_id' => $repository->id,
        'repo' => $repository->name,
        'vcs_provider' => 'github',
        'project_name' => 'example-repository',
        'environment' => 'DEV',
        'base_version' => 'v1.1.0',
        'head_version' => 'v1.2.0',
        'package_name' => 'DEV-example-repository-v1.1.0-to-v1.2.0',
        'status' => 'completed',
        'created_at' => now()->subMinutes(5),
        'updated_at' => now()->subMinutes(5),
    ]);
    $deletedPackage->delete();

    $latestPackage = DeploymentJob::create([
        'user_id' => $user->id,
        'repository_id' => $repository->id,
        'repo' => $repository->name,
        'vcs_provider' => 'github',
        'project_name' => 'example-repository',
        'environment' => 'QA',
        'base_version' => 'v1.2.0',
        'head_version' => 'v1.3.0',
        'package_name' => 'QA-example-repository-v1.2.0-to-v1.3.0',
        'status' => 'completed',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('packages.index'));

    $response
        ->assertOk()
        ->assertViewIs('packages')
        ->assertViewHas('packageClientIndex', function (array $packageClientIndex) use ($olderPackage, $latestPackage): bool {
            return array_key_exists($olderPackage->id, $packageClientIndex)
                && array_key_exists($latestPackage->id, $packageClientIndex)
                && str_contains($packageClientIndex[$latestPackage->id]['search'], 'qa-example-repository');
        });
});
