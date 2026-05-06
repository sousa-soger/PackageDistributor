<?php

namespace App\Http\Controllers;

use App\Exceptions\OAuthTokenRefreshException;
use App\Models\User;
use App\Services\OAuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GitLabProjectController extends Controller
{
    private function baseUrl(): string
    {
        return rtrim(config('services.gitlab.base_url'), '/');
    }

    public function index(Request $request)
    {
        return view('projects', [
            'gitlabConnected' => filled($request->user()->gitlab_token),
            'gitlabUsername' => $request->user()->gitlab_username,
        ]);
    }

    public function list(Request $request, OAuthTokenService $oauthTokens): JsonResponse
    {
        $user = $request->user();

        [$token, $authResponse] = $this->gitlabToken($user, $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("{$this->baseUrl()}/api/v4/projects", [
                'membership' => 'true',
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
                $accessLevel = data_get($project, 'permissions.project_access.access_level')
                    ?? data_get($project, 'permissions.group_access.access_level')
                    ?? 0;

                return [
                    'id' => $project['id'],
                    'icon' => 'gitlab',
                    'name' => $project['name'],
                    'path' => $project['path_with_namespace'],
                    'description' => $project['description'] ?: 'No description.',
                    'lastActivity' => $project['last_activity_at'],
                    'visibility' => $project['visibility'] ?? 'private',
                    'access_level' => $accessLevel,
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

    public function explore(Request $request, OAuthTokenService $oauthTokens): JsonResponse
    {
        $user = $request->user();

        [$token, $authResponse] = $this->gitlabToken($user, $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("{$this->baseUrl()}/api/v4/projects", [
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
                    'access_level' => 0, // explore projects — no guaranteed membership
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

    public function searchUsers(Request $request, OAuthTokenService $oauthTokens): JsonResponse
    {
        $user = $request->user();

        [$token, $authResponse] = $this->gitlabToken($user, $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        $q = $request->query('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("{$this->baseUrl()}/api/v4/users", [
                'search' => $q,
                'per_page' => 10,
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to search GitLab users.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json(
            collect($response->json())->map(fn ($u) => [
                'id' => $u['id'],
                'username' => $u['username'],
                'name' => $u['name'],
                'avatar_url' => $u['avatar_url'] ?? null,
            ])->values()
        );
    }

    public function inviteMember(Request $request, OAuthTokenService $oauthTokens, int $projectId): JsonResponse
    {
        $user = $request->user();

        [$token, $authResponse] = $this->gitlabToken($user, $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'access_level' => ['required', 'integer', 'in:10,20,30,40,50'],
        ]);

        $response = Http::withToken($token)
            ->acceptJson()
            ->post("{$this->baseUrl()}/api/v4/projects/{$projectId}/members", [
                'user_id' => $validated['user_id'],
                'access_level' => $validated['access_level'],
            ]);

        if ($response->failed()) {
            $body = $response->json();

            return response()->json([
                'message' => data_get($body, 'message', 'Failed to add member.'),
            ], $response->status());
        }

        return response()->json(['message' => 'Member added successfully.']);
    }

    public function getMembers(Request $request, OAuthTokenService $oauthTokens, int $projectId): JsonResponse
    {
        $user = $request->user();

        [$token, $authResponse] = $this->gitlabToken($user, $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("{$this->baseUrl()}/api/v4/projects/{$projectId}/members/all", [
                'per_page' => 100,
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to fetch project members.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json(
            collect($response->json())->map(fn ($m) => [
                'id' => $m['id'],
                'username' => $m['username'],
                'name' => $m['name'],
                'avatar_url' => $m['avatar_url'] ?? null,
                'access_level' => $m['access_level'],
                'expires_at' => $m['expires_at'] ?? null,
            ])->sortByDesc('access_level')->values()
        );
    }

    public function updateMemberRole(Request $request, OAuthTokenService $oauthTokens, int $projectId, int $userId): JsonResponse
    {
        $user = $request->user();

        [$token, $authResponse] = $this->gitlabToken($user, $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        $validated = $request->validate([
            'access_level' => ['required', 'integer', 'in:10,20,30,40,50'],
        ]);

        $response = Http::withToken($token)
            ->acceptJson()
            ->put("{$this->baseUrl()}/api/v4/projects/{$projectId}/members/{$userId}", [
                'access_level' => $validated['access_level'],
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to update member role.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json(['message' => 'Role updated successfully.']);
    }

    public function removeMember(Request $request, OAuthTokenService $oauthTokens, int $projectId, int $userId): JsonResponse
    {
        $user = $request->user();

        [$token, $authResponse] = $this->gitlabToken($user, $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->delete("{$this->baseUrl()}/api/v4/projects/{$projectId}/members/{$userId}");

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to remove member.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json(['message' => 'Member removed successfully.']);
    }

    /**
     * Get branches and tags for a GitLab project.
     * Used by the packaging wizard when vcs_provider = 'gitlab'.
     *
     * GET /gitlab/projects/{projectId}/versions
     */
    public function getProjectVersions(Request $request, OAuthTokenService $oauthTokens, int $projectId): JsonResponse
    {
        $user = $request->user();

        [$token, $authResponse] = $this->gitlabToken($user, $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        $base = $this->baseUrl();

        $branchesResponse = Http::withToken($token)
            ->get("{$base}/api/v4/projects/{$projectId}/repository/branches", [
                'per_page' => 100,
            ]);

        $tagsResponse = Http::withToken($token)
            ->get("{$base}/api/v4/projects/{$projectId}/repository/tags", [
                'per_page' => 100,
            ]);

        if ($branchesResponse->failed() || $tagsResponse->failed()) {
            return response()->json([
                'message' => 'Failed to fetch project versions.',
            ], 500);
        }

        $branches = collect($branchesResponse->json())->map(fn ($b) => ['name' => $b['name']]);
        $tags = collect($tagsResponse->json())->map(fn ($t) => ['name' => $t['name']]);

        return response()->json([
            'branches' => $branches->values(),
            'tags' => $tags->values(),
            'releases' => [], // GitLab tags serve as releases
        ]);
    }

    /**
     * @return array{0: string|null, 1: JsonResponse|null}
     */
    private function gitlabToken(User $user, OAuthTokenService $oauthTokens): array
    {
        if (! $user->gitlab_token) {
            return [null, response()->json(['message' => 'GitLab is not connected.'], 401)];
        }

        try {
            $token = $oauthTokens->accessToken($user, 'gitlab');
        } catch (OAuthTokenRefreshException $e) {
            return [null, response()->json([
                'message' => $e->getMessage(),
                'redirect_url' => route('gitlab.oauth.redirect'),
                'requires_oauth' => true,
            ], 409)];
        }

        if (! $token) {
            return [null, response()->json([
                'message' => 'Reconnect GitLab OAuth before making GitLab API requests.',
                'redirect_url' => route('gitlab.oauth.redirect'),
                'requires_oauth' => true,
            ], 409)];
        }

        return [$token, null];
    }
}
