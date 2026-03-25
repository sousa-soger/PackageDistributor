<?php

namespace App\Http\Controllers;

use App\Services\GitHubService;

class PackageController extends Controller
{
    public function index(GitHubService $github)
    {
        $repos = config('github-repos');

        $data = [];

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

            $data[] = [
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

        return view('packages.index', compact('data'));
    }
}