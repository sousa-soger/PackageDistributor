<?php

namespace App\Http\Controllers;

class PackageController extends Controller
{
    public function indexV3()
    {
        $repositories = array_map(static function (array $item) {
            return [
                'id' => $item['id'],
                'label' => $item['label'],
                'owner' => $item['owner'],
                'repo' => $item['repo'],
            ];
        }, config('github-repos'));

        $packages = \App\Models\DeploymentJob::where('user_id', auth()->id())
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        $queuedPackages = \App\Models\DeploymentJob::where('user_id', auth()->id())
            ->whereIn('status', ['running', 'queued', 'pending'])
            ->orderByDesc('created_at')
            ->get();

        return view('new-packageV3', compact('repositories', 'packages', 'queuedPackages'));
    }
    public function donePackages()
    {
        $packages = \App\Models\DeploymentJob::where('user_id', auth()->id())
            ->where('message', 'Done')
            ->orderByDesc('created_at')
            ->get();

        return view('packages.done', compact('packages'));
    }
    public function queuedPackages()
    {
        $queuedPackages = \App\Models\DeploymentJob::where('user_id', auth()->id())
            ->whereIn('status', ['running', 'queued', 'pending'])
            ->orderByDesc('created_at')
            ->get();

        return view('packages.queued', compact('queuedPackages'));
    }
}
