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

        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'Laravel-App',
        ])->get("https://api.github.com/repos/{$repo}");

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to fetch repository info',
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
        ];

        $branchesResponse = Http::withHeaders($headers)
            ->get("https://api.github.com/repos/{$repo}/branches");

        $tagsResponse = Http::withHeaders($headers)
            ->get("https://api.github.com/repos/{$repo}/tags");

        $commitsResponse = Http::withHeaders($headers)
            ->get("https://api.github.com/repos/{$repo}/commits", [
                'per_page' => 15,
            ]);

        if ($branchesResponse->failed() || $tagsResponse->failed() || $commitsResponse->failed()) {
            return response()->json([
                'message' => 'Failed to fetch repository versions'
            ], 500);
        }

        return response()->json([
            'branches' => $branchesResponse->json(),
            'tags' => $tagsResponse->json(),
            'commits' => $commitsResponse->json(),
        ]);
    }
}