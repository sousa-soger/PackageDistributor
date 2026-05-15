<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('session status endpoint reports no active session for guests', function () {
    $this->getJson(route('auth.session-status'))
        ->assertOk()
        ->assertExactJson([
            'active' => false,
        ]);
});

test('session status endpoint reports the active username for authenticated users', function () {
    $user = User::factory()->create([
        'email' => 'ldap.user@example.com',
        'ldap_username' => 'ldap.user',
    ]);

    $this->actingAs($user)
        ->getJson(route('auth.session-status'))
        ->assertOk()
        ->assertExactJson([
            'active' => true,
            'username' => 'ldap.user',
        ]);
});

test('revoke current session endpoint logs out the active user', function () {
    $user = User::factory()->create([
        'ldap_username' => 'existing.user',
    ]);

    $this->actingAs($user)
        ->postJson(route('auth.revoke-current-session'))
        ->assertOk()
        ->assertJsonPath('active', false)
        ->assertJsonStructure(['active', 'csrfToken']);

    $this->getJson(route('auth.session-status'))
        ->assertOk()
        ->assertExactJson([
            'active' => false,
        ]);
});

test('local login revokes older server side sessions before starting a new session', function () {
    $user = User::factory()->create([
        'email' => 'local.user@example.com',
        'name' => 'local-user',
        'password' => Hash::make('secret-pass'),
    ]);

    DB::table('sessions')->insert([
        'id' => 'legacy-session-id',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test-payload',
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->post(route('login.user'), [
        'loginmode' => 'local',
        'loginusername' => 'local.user@example.com',
        'loginpassword' => 'secret-pass',
    ]);

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('success', 'Previous session revoked, New session started as local.user@example.com');
    $this->assertAuthenticatedAs($user);

    expect(DB::table('sessions')->where('id', 'legacy-session-id')->exists())->toBeFalse();
});
