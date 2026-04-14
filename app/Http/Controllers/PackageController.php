<?php

namespace App\Http\Controllers;

class PackageController extends Controller
{
    public function index()
    {
        $repositories = array_map(static function (array $item) {
            return [
                'id' => $item['id'],
                'label' => $item['label'],
                'owner' => $item['owner'],
                'repo' => $item['repo'],
            ];
        }, config('github-repos'));

        return view('new-package', compact('repositories'));
    }

    public function indexV2()
    {
        $repositories = array_map(static function (array $item) {
            return [
                'id' => $item['id'],
                'label' => $item['label'],
                'owner' => $item['owner'],
                'repo' => $item['repo'],
            ];
        }, config('github-repos'));

        return view('new-packageV2', compact('repositories'));
    }

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

        return view('new-packageV3', compact('repositories', 'packages'));
    }
    public function donePackages()
    {
        $packages = DeploymentJob::where('user_id', auth()->id())
            ->where('message', 'Done')
            ->orderByDesc('created_at')
            ->get();

        return view('packages.done', compact('packages'));
    }
}
