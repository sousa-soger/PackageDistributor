<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('settings', [
            'user' => $user,
            'githubConnected' => (bool) $user->github_id,
            'githubUsername' => $user->github_username,
            'githubConnectedAt' => $user->github_connected_at?->format('d/m/Y'),
            'gitlabConnected' => (bool) $user->gitlab_id,
            'gitlabUsername' => $user->gitlab_username,
            'gitlabConnectedAt' => $user->gitlab_connected_at?->format('d/m/Y'),
        ]);
    }
}
