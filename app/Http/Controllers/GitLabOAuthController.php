<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GitLabOAuthController extends Controller
{
    private function gitlabDriver()
    {
        return Socialite::driver('gitlab')
            ->setHost(config('services.gitlab.base_url'));
    }

    public function redirect(Request $request)
    {
        $request->session()->put(
            'gitlab_oauth_redirect_to',
            $request->query('return_to', 'projects')
        );

        return $this->gitlabDriver()
            ->scopes([
                'api',
                'read_user',
                'read_repository',
            ])
            ->stateless()
            ->redirect();
    }

    public function callback(Request $request)
    {
        $gitlabUser = $this->gitlabDriver()->stateless()->user();

        $request->user()->update([
            'gitlab_id' => $gitlabUser->getId(),
            'gitlab_username' => $gitlabUser->getNickname(),
            'gitlab_name' => $gitlabUser->getName(),
            'gitlab_email' => $gitlabUser->getEmail(),
            'gitlab_token' => $gitlabUser->token,
            'gitlab_refresh_token' => $gitlabUser->refreshToken,
            'gitlab_token_expires_at' => $gitlabUser->expiresIn
                ? now()->addSeconds($gitlabUser->expiresIn)
                : null,
            'gitlab_connected_at' => now(),
        ]);

        $routeName = $request->session()->pull('gitlab_oauth_redirect_to', 'projects');
        $routeParameters = $routeName === 'repositories'
            ? ['oauth_provider' => 'gitlab']
            : [];

        return redirect()
            ->route($routeName, $routeParameters)
            ->with('success', 'GitLab connected successfully.');
    }

    public function disconnect(Request $request)
    {
        $request->user()->update([
            'gitlab_id' => null,
            'gitlab_username' => null,
            'gitlab_name' => null,
            'gitlab_email' => null,
            'gitlab_token' => null,
            'gitlab_refresh_token' => null,
            'gitlab_token_expires_at' => null,
            'gitlab_connected_at' => null,
        ]);

        return redirect()
            ->route('projects')
            ->with('success', 'GitLab disconnected successfully.');
    }
}
