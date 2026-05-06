<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GitHubOAuthController extends Controller
{
    private const SCOPES = [
        'repo',
        'read:user',
        'user:email',
    ];

    private function githubDriver(): Provider
    {
        return Socialite::driver('github')
            ->scopes(self::SCOPES);
    }

    public function redirect(Request $request): SymfonyRedirectResponse
    {
        $request->session()->put(
            'github_oauth_redirect_to',
            $request->query('return_to', 'repositories')
        );

        return $this->githubDriver()
            ->with(['scope' => implode(' ', self::SCOPES)])
            ->stateless()
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $githubUser = $this->githubDriver()->stateless()->user();
        $expiresIn = $githubUser->expiresIn ?? null;

        $request->user()->update([
            'github_id' => $githubUser->getId(),
            'github_username' => $githubUser->getNickname(),
            'github_name' => $githubUser->getName(),
            'github_email' => $githubUser->getEmail(),
            'github_token' => $githubUser->token,
            'github_refresh_token' => $githubUser->refreshToken ?? null,
            'github_token_expires_at' => $expiresIn
                ? now()->addSeconds((int) $expiresIn)
                : null,
            'github_connected_at' => now(),
        ]);

        $routeName = $request->session()->pull('github_oauth_redirect_to', 'repositories');
        $routeParameters = $routeName === 'repositories'
            ? ['oauth_provider' => 'github']
            : [];

        return redirect()
            ->route($routeName, $routeParameters)
            ->with('success', 'GitHub connected successfully.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->update([
            'github_id' => null,
            'github_username' => null,
            'github_name' => null,
            'github_email' => null,
            'github_token' => null,
            'github_refresh_token' => null,
            'github_token_expires_at' => null,
            'github_connected_at' => null,
        ]);

        return redirect()
            ->route('repositories')
            ->with('success', 'GitHub disconnected successfully.');
    }
}
