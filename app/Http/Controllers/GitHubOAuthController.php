<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GitHubOAuthController extends Controller
{
    private function githubDriver()
    {
        return Socialite::driver('github');
    }

    public function redirect(Request $request)
    {
        $request->session()->put(
            'github_oauth_redirect_to',
            $request->query('return_to', 'repositories')
        );

        return $this->githubDriver()
            ->scopes([
                'read:user',
                'repo',
                'user:email',
            ])
            ->stateless()
            ->redirect();
    }

    public function callback(Request $request)
    {
        $githubUser = $this->githubDriver()->stateless()->user();

        $request->user()->update([
            'github_id' => $githubUser->getId(),
            'github_username' => $githubUser->getNickname(),
            'github_name' => $githubUser->getName(),
            'github_email' => $githubUser->getEmail(),
            'github_avatar' => $githubUser->getAvatar(),
            'github_token' => $githubUser->token,
            'github_refresh_token' => $githubUser->refreshToken,
            'github_token_expires_at' => $githubUser->expiresIn
                ? now()->addSeconds($githubUser->expiresIn)
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

    public function disconnect(Request $request)
    {
        $request->user()->update([
            'github_id' => null,
            'github_username' => null,
            'github_name' => null,
            'github_email' => null,
            'github_avatar' => null,
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
