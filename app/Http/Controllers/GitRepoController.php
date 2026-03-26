<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GitHubController extends Controller
{
    public function repoInfo(Request $request)
    {
        $repo = $request->query('repo');

        if (!$repo) {
            return response()->json([
                'message' => 'Repository is required'
            ], 422);
        }

        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'Laravel-App',
            'Authorization' => 'Bearer ' . config('services.github.token'),
        ];

        $response = Http::withHeaders($headers)
            ->get("https://api.github.com/repos/{$repo}");

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to fetch repository info',
                'status' => $response->status(),
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json($response->json());
    }

    public function repoVersions(Request $request)
    {
        $repo = $request->query('repo');

        if (!$repo) {
            return response()->json(['message' => 'Repository is required'], 422);
        }

        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'Laravel-App',
            'Authorization' => 'Bearer ' . config('services.github.token'),
        ];

        $branchesResponse = Http::withHeaders($headers)
            ->get("https://api.github.com/repos/{$repo}/branches");

        $tagsResponse = Http::withHeaders($headers)
            ->get("https://api.github.com/repos/{$repo}/tags");

        $releasesResponse = Http::withHeaders($headers)
            ->get("https://api.github.com/repos/{$repo}/releases");

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
}