<?php

use App\Models\Repository;
use App\Models\User;
use App\Services\DeploymentPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function previewRepositoryFor(User $owner, array $attributes = []): Repository
{
    return $owner->repositories()->create(array_merge([
        'branches' => ['main', 'release'],
        'default_branch' => 'main',
        'display_name' => 'Preview Source',
        'name' => 'acme/preview-source',
        'provider' => 'github',
        'status' => 'connected',
        'tags' => ['v1.0.0', 'v1.1.0'],
        'url' => 'https://github.com/acme/preview-source',
    ], $attributes));
}

test('repository package creator can preview detected changes', function () {
    $owner = User::factory()->create();
    $creator = User::factory()->create();
    $repository = previewRepositoryFor($owner, ['access_token' => 'owner-pat-token']);

    $repository->members()->attach($creator->id, ['role' => 'creator', 'source' => 'ldap']);

    $this->mock(DeploymentPackageService::class, function (MockInterface $mock) use ($repository) {
        $mock->shouldReceive('previewChanges')
            ->once()
            ->with('v1.0.0', 'v1.1.0', $repository->name, 'github', 'owner-pat-token')
            ->andReturn([
                'added' => 2,
                'deleted' => 1,
                'modified' => 4,
                'total' => 7,
            ]);
    });

    $this->actingAs($creator)
        ->postJson(route('deployments.preview-changes'), [
            'base_version' => 'v1.0.0',
            'head_version' => 'v1.1.0',
            'repository_id' => $repository->id,
        ])
        ->assertOk()
        ->assertJson([
            'summary' => [
                'added' => 2,
                'deleted' => 1,
                'modified' => 4,
                'total' => 7,
            ],
        ]);
});

test('repository viewer cannot preview detected changes', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $repository = previewRepositoryFor($owner);

    $repository->members()->attach($viewer->id, ['role' => 'viewer', 'source' => 'ldap']);

    $this->actingAs($viewer)
        ->postJson(route('deployments.preview-changes'), [
            'base_version' => 'v1.0.0',
            'head_version' => 'v1.1.0',
            'repository_id' => $repository->id,
        ])
        ->assertForbidden();
});

test('preview detected changes validates two different versions', function () {
    $owner = User::factory()->create();
    $repository = previewRepositoryFor($owner);

    $this->actingAs($owner)
        ->postJson(route('deployments.preview-changes'), [
            'base_version' => 'v1.0.0',
            'head_version' => 'v1.0.0',
            'repository_id' => $repository->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['base_version']);
});
