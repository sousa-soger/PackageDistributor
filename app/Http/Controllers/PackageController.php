<?php

namespace App\Http\Controllers;

use App\Services\GitHubService;
use Illuminate\Http\Request;

class PackageController extends Controller
{
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

        return view('new-package', compact('repositories'));
    }

    public function repoData(Request $request, GitHubService $github)
    {
        $repoId = $request->query('repo');

        [$owner, $repo] = explode('/', $repoId);

        return response()->json([
            'branches' => $github->getBranches($owner, $repo),
            'tags' => $github->getTags($owner, $repo),
            'releases' => $github->getReleases($owner, $repo),
            'versions' => [
                'main' => $github->getFile($owner, $repo, 'release/version.json', 'main'),
                'qa'   => $github->getFile($owner, $repo, 'release/version.json', 'qa'),
                'dev'  => $github->getFile($owner, $repo, 'release/version.json', 'dev'),
            ],
        ]);
    }
}