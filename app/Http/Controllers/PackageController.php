<?php

namespace App\Http\Controllers;

use App\Models\DeploymentJob;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function indexV3(Request $request)
    {
        $vcsProvider = config('packaging.vcs_provider', 'github');
        $user = $request->user();
        $gitlabConnected = filled($user->gitlab_token);

        // Only needed for the GitHub dropdown; GitLab fetches dynamically.
        $repositories = $vcsProvider === 'github'
            ? array_map(static function (array $item) {
                return [
                    'id' => $item['id'],
                    'label' => $item['label'],
                    'owner' => $item['owner'],
                    'repo' => $item['repo'],
                ];
            }, config('github-repos'))
            : [];

        $packages = DeploymentJob::where('user_id', auth()->id())
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        $queuedPackages = DeploymentJob::where('user_id', auth()->id())
            ->whereIn('status', ['running', 'queued', 'pending'])
            ->orderByDesc('created_at')
            ->get();

        return view('new-packageV3', compact('repositories', 'packages', 'queuedPackages', 'vcsProvider', 'gitlabConnected'));
    }

    public function createPackage(Request $request)
    {
        $vcsProvider = config('packaging.vcs_provider', 'github');
        $user = $request->user();
        $gitlabConnected = filled($user->gitlab_token);

        $repositories = $vcsProvider === 'github'
            ? array_map(static function (array $item) {
                return [
                    'id' => $item['id'],
                    'label' => $item['label'],
                    'owner' => $item['owner'],
                    'repo' => $item['repo'],
                ];
            }, config('github-repos'))
            : [];

        $packages = DeploymentJob::where('user_id', auth()->id())
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        $queuedPackages = DeploymentJob::where('user_id', auth()->id())
            ->whereIn('status', ['running', 'queued', 'pending'])
            ->orderByDesc('created_at')
            ->get();

        return view('create-package', compact('repositories', 'packages', 'queuedPackages', 'vcsProvider', 'gitlabConnected'));
    }

    public function donePackages()
    {
        $packages = DeploymentJob::where('user_id', auth()->id())
            ->where('message', 'Done')
            ->orderByDesc('created_at')
            ->get();

        return view('packages.done', compact('packages'));
    }

    public function packages(Request $request)
    {
        $packages = DeploymentJob::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('packages', compact('packages'));
    }

    public function queuedPackages()
    {
        $queuedPackages = DeploymentJob::where('user_id', auth()->id())
            ->whereIn('status', ['running', 'queued', 'pending'])
            ->orderByDesc('created_at')
            ->get();

        return view('packages.queued', compact('queuedPackages'));
    }
}
