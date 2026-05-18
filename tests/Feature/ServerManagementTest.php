<?php

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validServerManagementPayload(array $overrides = []): array
{
    return array_merge([
        'auto_deploy_enabled' => '1',
        'auto_deploy_strategy' => 'on_package_ready',
        'deploy_path' => '/var/www/cybix',
        'environment' => 'DEV',
        'health_check_url' => 'https://example.com/up',
        'host' => 'dev.example.com',
        'name' => 'Development App Server',
        'notes' => 'Deploy window is after package generation.',
        'port' => '22',
        'production_approval_required' => '0',
        'project_id' => null,
        'ssh_user' => 'deploy',
    ], $overrides);
}

test('servers page lists only the authenticated users servers', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Server::factory()->for($user)->create([
        'name' => 'QA App Server',
        'host' => 'qa.example.com',
    ]);

    Server::factory()->for($otherUser)->create([
        'name' => 'Other User Server',
        'host' => 'hidden.example.com',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('servers.index'));

    $response
        ->assertOk()
        ->assertViewIs('servers')
        ->assertSee('QA App Server')
        ->assertDontSee('Other User Server');
});

test('users can create a server target', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create([
        'name' => 'Cybix Web',
        'slug' => 'cybix-web',
        'description' => 'Main web app',
        'color' => 'from-brand-rose to-brand-iris',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('servers.store'), validServerManagementPayload([
            'environment' => 'QA',
            'name' => 'QA App Server',
            'project_id' => $project->id,
        ]));

    $response
        ->assertRedirectToRoute('servers.index')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('servers', [
        'auto_deploy_enabled' => true,
        'environment' => 'QA',
        'host' => 'dev.example.com',
        'name' => 'QA App Server',
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);
});

test('server names must be unique per user', function () {
    $user = User::factory()->create();

    Server::factory()->for($user)->create([
        'name' => 'Production App Server',
    ]);

    $response = $this
        ->actingAs($user)
        ->from(route('servers.index'))
        ->post(route('servers.store'), validServerManagementPayload([
            'environment' => 'PROD',
            'name' => 'Production App Server',
        ]));

    $response->assertInvalid(['name']);
});

test('users can update their own server target', function () {
    $user = User::factory()->create();
    $server = Server::factory()->for($user)->create([
        'name' => 'Old Server',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('servers.update', $server), validServerManagementPayload([
            'auto_deploy_enabled' => '0',
            'environment' => 'PROD',
            'host' => 'prod.example.com',
            'name' => 'Production App Server',
            'production_approval_required' => '1',
        ]));

    $response
        ->assertRedirectToRoute('servers.index')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('servers', [
        'auto_deploy_enabled' => false,
        'environment' => 'PROD',
        'host' => 'prod.example.com',
        'id' => $server->id,
        'name' => 'Production App Server',
        'production_approval_required' => true,
    ]);
});

test('users cannot update another users server', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $server = Server::factory()->for($otherUser)->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('servers.update', $server), validServerManagementPayload());

    $response->assertForbidden();
});
