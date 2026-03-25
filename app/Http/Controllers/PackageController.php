<?php

namespace App\Http\Controllers;

use App\Models\Version;
use App\Services\GitHubService;
use Illuminate\Http\Request;

class PackageController extends Controller
{   //** Get repo from local host dummy repo */

    /*public function create()
    {
        $repositories = Version::query()
            ->select('app_name')
            ->distinct()
            ->orderBy('app_name')
            ->pluck('app_name');

        $versions = Version::orderBy('app_name')
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        return view('new-package', compact('repositories', 'versions'));
    }*/

    //** Get repo from github */
    public function index(GitHubService $github)
    {
        $repos = config('github-repos');

        $repositories = [];

        foreach ($repos as $item) {
            $owner = $item['owner'];
            $repo  = $item['repo'];

            $repoInfo  = $github->getRepo($owner, $repo);
            $branches  = $github->getBranches($owner, $repo);
            $tags      = $github->getTags($owner, $repo);
            $releases  = $github->getReleases($owner, $repo);

            $versionFromMain = $github->getFile($owner, $repo, 'release/version.json', 'main');
            $versionFromQa   = $github->getFile($owner, $repo, 'release/version.json', 'qa');
            $versionFromDev  = $github->getFile($owner, $repo, 'release/version.json', 'dev');

            $repositories[] = [
                'id' => "{$owner}/{$repo}",
                'label' => $item['label'],
                'owner' => $owner,
                'repo' => $repo,
                'repo_info' => $repoInfo,
                'branches' => $branches,
                'tags' => $tags,
                'releases' => $releases,
                'versions' => [
                    'main' => $versionFromMain,
                    'qa'   => $versionFromQa,
                    'dev'  => $versionFromDev,
                ],
            ];
        }

        return view('packages.index', compact('repositories'));
    }
}