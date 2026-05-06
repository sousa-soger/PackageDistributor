<?php

namespace App\Http\Controllers;

use App\Exceptions\OAuthTokenRefreshException;
use App\Services\GitHubService;
use App\Services\OAuthTokenService;
use Illuminate\Http\Request;

class GitHubController extends Controller
{
    public function repoInfo(Request $request, GitHubService $github, OAuthTokenService $oauthTokens)
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

        try {
            $response = $request->user()
                ? $oauthTokens->withFreshToken(
                    $request->user(),
                    'github',
                    fn (string $token) => $github->getRepository($owner, $name, $token)
                )
                : $github->getRepository($owner, $name);
        } catch (OAuthTokenRefreshException $e) {
            return $this->oauthReconnectResponse($e);
        }

        if ($response === null) {
            return response()->json([
                'message' => 'Connect your GitHub account first.',
                'redirect_url' => route('github.oauth.redirect'),
                'requires_oauth' => true,
            ], 409);
        }

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to fetch repository info',
                'status' => $response->status(),
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json($response->json());
    }

    public function repoVersions(Request $request, GitHubService $github, OAuthTokenService $oauthTokens)
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

        try {
            if ($request->user()) {
                $responses = $oauthTokens->withFreshToken(
                    $request->user(),
                    'github',
                    fn (string $token) => [
                        'branches' => $github->getBranches($owner, $name, $token),
                        'tags' => $github->getTags($owner, $name, $token),
                        'releases' => $github->getReleases($owner, $name, $token),
                    ]
                );
            } else {
                $responses = [
                    'branches' => $github->getBranches($owner, $name),
                    'tags' => $github->getTags($owner, $name),
                    'releases' => $github->getReleases($owner, $name),
                ];
            }
        } catch (OAuthTokenRefreshException $e) {
            return $this->oauthReconnectResponse($e);
        }

        if ($responses === null) {
            return response()->json([
                'message' => 'Connect your GitHub account first.',
                'redirect_url' => route('github.oauth.redirect'),
                'requires_oauth' => true,
            ], 409);
        }

        $branchesResponse = $responses['branches'];
        $tagsResponse = $responses['tags'];
        $releasesResponse = $responses['releases'];

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

    public function rateLimit(Request $request, GitHubService $github, OAuthTokenService $oauthTokens)
    {
        try {
            $response = $request->user()
                ? $oauthTokens->withFreshToken(
                    $request->user(),
                    'github',
                    fn (string $token) => $github->getRateLimit($token)
                )
                : $github->getRateLimit();
        } catch (OAuthTokenRefreshException $e) {
            return $this->oauthReconnectResponse($e);
        }

        if ($response === null) {
            return response()->json([
                'message' => 'Connect your GitHub account first.',
                'redirect_url' => route('github.oauth.redirect'),
                'requires_oauth' => true,
            ], 409);
        }

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

    protected function oauthReconnectResponse(OAuthTokenRefreshException $e)
    {
        return response()->json([
            'message' => $e->getMessage(),
            'redirect_url' => route('github.oauth.redirect'),
            'requires_oauth' => true,
        ], 409);
    }
}
