<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GitLabProjectController extends Controller
{
    public function index(Request $request)
    {
        return view('projects', [
            'gitlabConnected' => filled($request->user()->gitlab_token),
            'gitlabUsername' => $request->user()->gitlab_username,
        ]);
    }

    public function list(Request $request)
    {
        $user = $request->user();

        if (! $user->gitlab_token) {
            return response()->json([
                'message' => 'GitLab is not connected.',
            ], 401);
        }

        $baseUrl = rtrim(config('services.gitlab.base_url'), '/');

        $response = Http::withToken($user->gitlab_token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects", [
                'membership' => 'true', // needs to be string, Laravel HTTP uses Guzzle which takes bolean as 1 and 0 instead of true and false when GitLab API only accepts true and false
                'per_page' => 100,
                'order_by' => 'last_activity_at',
                'sort' => 'desc',
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to fetch GitLab projects.',
                'status' => $response->status(),
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json(
            collect($response->json())->map(function ($project) use ($user) {
                return [
                    'id' => $project['id'],
                    'icon' => 'gitlab',
                    'name' => $project['name'],
                    'path' => $project['path_with_namespace'],
                    'description' => $project['description'] ?: 'No description.',
                    'lastActivity' => $project['last_activity_at'],
                    'visibility' => $project['visibility'] ?? 'private',
                    'role' => null,
                    'category' => data_get($project, 'namespace.path') === $user->gitlab_username ? 'personal' : 'shared',
                    'web_url' => $project['web_url'],
                    'http_url_to_repo' => $project['http_url_to_repo'] ?? null,
                    'ssh_url_to_repo' => $project['ssh_url_to_repo'] ?? null,
                    'default_branch' => $project['default_branch'] ?? null,
                    'source' => 'member',
                ];
            })->values()
        );
    }

    public function explore(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->gitlab_token) {
            return response()->json([
                'message' => 'GitLab is not connected.',
            ], 401);
        }

        $baseUrl = rtrim(config('services.gitlab.base_url'), '/');

        $response = Http::withToken($user->gitlab_token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects", [
                'visibility' => 'internal',
                'per_page' => 100,
                'order_by' => 'last_activity_at',
                'sort' => 'desc',
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to fetch GitLab explore projects.',
                'status' => $response->status(),
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json(
            collect($response->json())->map(function ($project) use ($user) {
                return [
                    'id' => $project['id'],
                    'icon' => 'gitlab',
                    'name' => $project['name'],
                    'path' => $project['path_with_namespace'],
                    'description' => $project['description'] ?: 'No description.',
                    'lastActivity' => $project['last_activity_at'],
                    'visibility' => $project['visibility'] ?? 'internal',
                    'role' => null,
                    'category' => data_get($project, 'namespace.path') === $user->gitlab_username ? 'personal' : 'shared',
                    'web_url' => $project['web_url'],
                    'http_url_to_repo' => $project['http_url_to_repo'] ?? null,
                    'ssh_url_to_repo' => $project['ssh_url_to_repo'] ?? null,
                    'default_branch' => $project['default_branch'] ?? null,
                    'source' => 'explore',
                ];
            })->values()
        );
    }
}
