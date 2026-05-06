<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('github oauth redirect requests private repository access with github scope separator', function () {
    config([
        'services.github.client_id' => 'github-client',
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

test('github oauth repository connection redirects when token is missing private repo scope', function () {
    config([
        'services.github.api_url' => 'https://api.github.test',
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
