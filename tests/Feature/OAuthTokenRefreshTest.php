<?php

use App\Models\User;
use App\Services\OAuthTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('expired gitlab oauth token is refreshed and persisted', function () {
    config([
        'services.gitlab.base_url' => 'https://gitlab.example.test',
        'services.gitlab.client_id' => 'gitlab-client',
        'services.gitlab.client_secret' => 'gitlab-secret',
        'services.gitlab.oauth_token_url' => null,
        'services.gitlab.redirect' => 'https://app.example.test/gitlab/oauth/callback',
    ]);

    Http::fake([
        'https://gitlab.example.test/oauth/token' => Http::response([
            'access_token' => 'fresh-gitlab-token',
            'expires_in' => 7200,
            'refresh_token' => 'rotated-gitlab-refresh-token',
            'token_type' => 'Bearer',
        ]),
    ]);

    $user = User::factory()->create([
        'gitlab_token' => 'expired-gitlab-token',
        'gitlab_refresh_token' => 'old-gitlab-refresh-token',
        'gitlab_token_expires_at' => now()->subMinute(),
    ]);

    $token = app(OAuthTokenService::class)->accessToken($user, 'gitlab');

    expect($token)->toBe('fresh-gitlab-token');

    $user->refresh();

    expect($user->gitlab_token)->toBe('fresh-gitlab-token')
        ->and($user->gitlab_refresh_token)->toBe('rotated-gitlab-refresh-token')
        ->and($user->gitlab_token_expires_at->greaterThan(now()->addMinutes(100)))->toBeTrue();

    Http::assertSent(function ($request) {
        parse_str($request->body(), $payload);

        return $request->url() === 'https://gitlab.example.test/oauth/token'
            && $payload['grant_type'] === 'refresh_token'
            && $payload['refresh_token'] === 'old-gitlab-refresh-token'
            && $payload['client_id'] === 'gitlab-client'
            && $payload['client_secret'] === 'gitlab-secret'
            && $payload['redirect_uri'] === 'https://app.example.test/gitlab/oauth/callback';
    });
});

test('github api request is retried once after unauthorized response', function () {
    config([
        'services.github.client_id' => 'github-client',
        'services.github.client_secret' => 'github-secret',
        'services.github.oauth_token_url' => 'https://github.example.test/login/oauth/access_token',
    ]);

    Http::fake([
        'https://api.github.test/repos/acme/demo' => Http::sequence()
            ->push(['message' => 'Bad credentials'], 401)
            ->push(['id' => 123, 'default_branch' => 'main'], 200),
        'https://github.example.test/login/oauth/access_token' => Http::response([
            'access_token' => 'fresh-github-token',
            'expires_in' => 28800,
            'refresh_token' => 'rotated-github-refresh-token',
            'token_type' => 'bearer',
        ]),
    ]);

    $user = User::factory()->create([
        'github_token' => 'stale-github-token',
        'github_refresh_token' => 'old-github-refresh-token',
        'github_token_expires_at' => now()->addHour(),
    ]);

    $response = app(OAuthTokenService::class)->withFreshToken(
        $user,
        'github',
        fn (string $token) => Http::withToken($token)->get('https://api.github.test/repos/acme/demo')
    );

    expect($response->ok())->toBeTrue()
        ->and($response->json('id'))->toBe(123);

    $user->refresh();

    expect($user->github_token)->toBe('fresh-github-token')
        ->and($user->github_refresh_token)->toBe('rotated-github-refresh-token');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.github.test/repos/acme/demo'
        && $request->hasHeader('Authorization', 'Bearer stale-github-token'));

    Http::assertSent(fn ($request) => $request->url() === 'https://api.github.test/repos/acme/demo'
        && $request->hasHeader('Authorization', 'Bearer fresh-github-token'));
});
