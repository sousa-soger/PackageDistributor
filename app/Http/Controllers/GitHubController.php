<?php

namespace App\Http\Controllers;

use App\Services\GitHubService;
use Illuminate\Http\Request;

class GitHubController extends Controller
{
    public function repoInfo(Request $request, GitHubService $github)
    {
        $repo = $request->query('repo');

        if (! $repo) {
            return response()->json([
                'message' => 'Repository is required',
            ], 422);
        }

        $parsed = $this->parseOwnerRepo($repo);
        if ($parsed === null) {
            return response()->json([
                'message' => 'Invalid repository format (expected owner/repo)',
            ], 422);
        }

        [$owner, $name] = $parsed;

        $response = $github->getRepository($owner, $name);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to fetch repository info',
                'status' => $response->status(),
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json($response->json());
    }

    public function repoVersions(Request $request, GitHubService $github)
    {
        $repo = $request->query('repo');

        if (! $repo) {
            return response()->json(['message' => 'Repository is required'], 422);
        }

        $parsed = $this->parseOwnerRepo($repo);
        if ($parsed === null) {
            return response()->json([
                'message' => 'Invalid repository format (expected owner/repo)',
            ], 422);
        }

        [$owner, $name] = $parsed;

        $branchesResponse = $github->getBranches($owner, $name);
        $tagsResponse = $github->getTags($owner, $name);
        $releasesResponse = $github->getReleases($owner, $name);

        if ($branchesResponse->failed() || $tagsResponse->failed() || $releasesResponse->failed()) {
            return response()->json([
                'message' => 'Failed to fetch repository versions',
                'branches_status' => $branchesResponse->status(),
                'branches_error' => $branchesResponse->json(),
                'tags_status' => $tagsResponse->status(),
                'tags_error' => $tagsResponse->json(),
                'releases_status' => $releasesResponse->status(),
                'releases_error' => $releasesResponse->json(),
            ], 500);
        }

        return response()->json([
            'branches' => $branchesResponse->json(),
            'tags' => $tagsResponse->json(),
            'releases' => $releasesResponse->json(),
        ]);
    }

    public function rateLimit(GitHubService $github)
    {
        $response = $github->getRateLimit();

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to fetch rate limit',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json($response->json());
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected function parseOwnerRepo(string $repo): ?array
    {
        $parts = explode('/', $repo, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return [$parts[0], $parts[1]];
    }
}
