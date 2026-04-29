<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Services\GitHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class RepositoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $repositories = $user->repositories()
            ->with('project')
            ->latest()
            ->get();

        $projects = $user->projects()
            ->orderBy('name')
            ->get(['id', 'name']);

        $oauthConnections = [
            'github' => (bool) $user->github_token,
            'gitlab' => (bool) $user->gitlab_token,
        ];

        return view('repositories', compact('oauthConnections', 'projects', 'repositories'));
    }

    public function store(Request $request, GitHubService $github): JsonResponse
    {
        $provider = $request->input('provider');

        $rules = [
            'access_token' => ['nullable', 'string', 'max:500'],
            'auth_method' => ['nullable', Rule::in(['oauth', 'pat'])],
            'display_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:500'],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id)
                ),
            ],
            'provider' => ['required', Rule::in(['github', 'gitlab', 'company-server', 'local-pc'])],
            'server_host' => ['nullable', 'string', 'max:255'],
            'server_path' => ['nullable', 'string', 'max:500'],
            'server_protocol' => ['nullable', Rule::in(['SSH', 'SFTP', 'HTTP', 'HTTPS'])],
            'url' => ['nullable', 'string', 'max:500'],
            'username' => ['nullable', 'string', 'max:255'],
        ];

        if (in_array($provider, ['github', 'gitlab'], true)) {
            $rules['auth_method'][] = 'required';
            $rules['access_token'][] = Rule::requiredIf(
                fn () => $request->input('auth_method') === 'pat'
            );
        }

        $validated = $request->validate($rules);
        $normalized = $this->normalizeRepositoryInput(
            $validated['provider'],
            $validated['name'],
            $validated['url'] ?? null
        );

        if (! $normalized) {
            return response()->json([
                'message' => 'Invalid repository format. Use owner/repo or a full repository URL.',
            ], 422);
        }

        $exists = $request->user()->repositories()
            ->where('provider', $validated['provider'])
            ->where('name', $normalized['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This repository is already connected to your account.',
            ], 422);
        }

        $metadata = [
            'access_token' => null,
            'branches' => [],
            'default_branch' => 'main',
            'external_id' => null,
            'status' => 'connected',
            'tags' => [],
        ];

        if ($validated['provider'] === 'github') {
            $oauthToken = $request->user()->github_token;
            $token = $validated['auth_method'] === 'pat'
                ? $validated['access_token']
                : $oauthToken;

            if ($validated['auth_method'] === 'oauth' && ! $oauthToken) {
                return response()->json([
                    'message' => 'Connect your GitHub account first to use OAuth.',
                    'redirect_url' => route('github.oauth.redirect', ['return_to' => 'repositories']),
                    'requires_oauth' => true,
                ], 409);
            }

            $metadata = $this->fetchGitHubMetadata($github, $normalized['name'], $token);

            if (! $metadata['ok']) {
                return response()->json([
                    'message' => $metadata['message'],
                ], 422);
            }

            $metadata['access_token'] = $validated['auth_method'] === 'pat'
                ? $validated['access_token']
                : null;
        }

        if ($validated['provider'] === 'gitlab') {
            $oauthToken = $request->user()->gitlab_token;
            $token = $validated['auth_method'] === 'pat'
                ? $validated['access_token']
                : $oauthToken;

            if ($validated['auth_method'] === 'oauth' && ! $oauthToken) {
                return response()->json([
                    'message' => 'Connect your GitLab account first to use OAuth.',
                    'redirect_url' => route('gitlab.oauth.redirect', ['return_to' => 'repositories']),
                    'requires_oauth' => true,
                ], 409);
            }

            $metadata = $this->fetchGitLabMetadata($normalized['name'], $token);

            if (! $metadata['ok']) {
                return response()->json([
                    'message' => $metadata['message'],
                ], 422);
            }

            $metadata['access_token'] = $validated['auth_method'] === 'pat'
                ? $validated['access_token']
                : null;
        }

        if (in_array($validated['provider'], ['company-server', 'local-pc'], true)) {
            $metadata['access_token'] = $validated['access_token'] ?? null;
            $metadata['status'] = 'connected';
        }

        $repository = $request->user()->repositories()->create([
            'access_token' => $metadata['access_token'],
            'branches' => $metadata['branches'],
            'default_branch' => $metadata['default_branch'],
            'display_name' => $validated['display_name'] ?? $normalized['display_name'],
            'external_id' => $metadata['external_id'],
            'last_synced_at' => now(),
            'name' => $normalized['name'],
            'project_id' => $validated['project_id'] ?? null,
            'provider' => $validated['provider'],
            'server_host' => $validated['server_host'] ?? $normalized['server_host'],
            'server_path' => $validated['server_path'] ?? null,
            'server_protocol' => $validated['server_protocol'] ?? $normalized['server_protocol'],
            'status' => $metadata['status'],
            'tags' => $metadata['tags'],
            'url' => $normalized['url'],
            'username' => $validated['username'] ?? null,
        ]);

        return response()->json([
            'message' => 'Repository connected successfully.',
            'repository' => $repository->fresh(),
        ], 201);
    }

    public function sync(Repository $repository, GitHubService $github): JsonResponse
    {
        $this->authorize('update', $repository);
        $repository->loadMissing('user');

        if ($repository->provider === 'github') {
            $token = $repository->access_token ?: $repository->user?->github_token;
            $metadata = $this->fetchGitHubMetadata($github, $repository->name, $token);

            if (! $metadata['ok']) {
                return response()->json([
                    'message' => $metadata['message'],
                ], 422);
            }

            $repository->update([
                'branches' => $metadata['branches'],
                'default_branch' => $metadata['default_branch'],
                'external_id' => $metadata['external_id'],
                'last_synced_at' => now(),
                'status' => 'connected',
                'tags' => $metadata['tags'],
            ]);
        }

        if ($repository->provider === 'gitlab') {
            $token = $repository->access_token ?: $repository->user?->gitlab_token;

            if (! $token) {
                $repository->update(['status' => 'needs-auth']);

                return response()->json([
                    'message' => 'Reconnect GitLab OAuth or provide a PAT to sync this repository.',
                ], 422);
            }

            $metadata = $this->fetchGitLabMetadata($repository->name, $token);

            if (! $metadata['ok']) {
                $repository->update(['status' => 'needs-auth']);

                return response()->json([
                    'message' => $metadata['message'],
                ], 422);
            }

            $repository->update([
                'branches' => $metadata['branches'],
                'default_branch' => $metadata['default_branch'],
                'external_id' => $metadata['external_id'],
                'last_synced_at' => now(),
                'status' => 'connected',
                'tags' => $metadata['tags'],
            ]);
        }

        return response()->json([
            'message' => 'Repository synced.',
            'repository' => $repository->fresh(),
        ]);
    }

    public function destroy(Repository $repository): JsonResponse
    {
        $this->authorize('delete', $repository);
        $repository->delete();

        return response()->json(['message' => 'Repository removed.']);
    }

    protected function fetchGitHubMetadata(GitHubService $github, string $name, ?string $token): array
    {
        $parsed = $this->parseGitHubOwnerRepo($name);

        if (! $parsed) {
            return [
                'message' => 'GitHub repositories must use the format owner/repo.',
                'ok' => false,
            ];
        }

        [$owner, $repo] = $parsed;

        $repoResponse = $github->getRepository($owner, $repo, $token);

        if ($repoResponse->failed()) {
            return [
                'message' => 'Could not reach this GitHub repository. Check the URL and credentials.',
                'ok' => false,
            ];
        }

        $repoData = $repoResponse->json();
        $branchesResponse = $github->getBranches($owner, $repo, $token);
        $tagsResponse = $github->getTags($owner, $repo, $token);

        return [
            'branches' => $branchesResponse->ok()
                ? collect($branchesResponse->json())->pluck('name')->all()
                : [],
            'default_branch' => $repoData['default_branch'] ?? 'main',
            'external_id' => isset($repoData['id']) ? (string) $repoData['id'] : null,
            'message' => null,
            'ok' => true,
            'status' => 'connected',
            'tags' => $tagsResponse->ok()
                ? collect($tagsResponse->json())->pluck('name')->all()
                : [],
        ];
    }

    protected function fetchGitLabMetadata(string $name, ?string $token): array
    {
        if (! $token) {
            return [
                'message' => 'Provide a GitLab PAT or connect GitLab OAuth first.',
                'ok' => false,
            ];
        }

        if (! $this->isGitLabProjectPath($name)) {
            return [
                'message' => 'GitLab repositories must use the format group/project or group/subgroup/project.',
                'ok' => false,
            ];
        }

        $projectPath = rawurlencode($name);
        $baseUrl = rtrim(config('services.gitlab.base_url', 'https://gitlab.com'), '/');

        $repoResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$projectPath}");

        if ($repoResponse->failed()) {
            return [
                'message' => 'Could not reach this GitLab repository. Check the URL and credentials.',
                'ok' => false,
            ];
        }

        $repoData = $repoResponse->json();

        $branchesResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$repoData['id']}/repository/branches");

        $tagsResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$repoData['id']}/repository/tags");

        return [
            'branches' => $branchesResponse->ok()
                ? collect($branchesResponse->json())->pluck('name')->all()
                : [],
            'default_branch' => $repoData['default_branch'] ?? 'main',
            'external_id' => isset($repoData['id']) ? (string) $repoData['id'] : null,
            'message' => null,
            'ok' => true,
            'status' => 'connected',
            'tags' => $tagsResponse->ok()
                ? collect($tagsResponse->json())->pluck('name')->all()
                : [],
        ];
    }

    protected function normalizeRepositoryInput(string $provider, string $rawName, ?string $rawUrl = null): ?array
    {
        $name = trim($rawName);
        $url = trim((string) $rawUrl);

        if (in_array($provider, ['github', 'gitlab'], true)) {
            $identifier = $this->extractRepositoryIdentifier($name);

            if (! $identifier && $url !== '') {
                $identifier = $this->extractRepositoryIdentifier($url);
            }

            if (! $identifier) {
                return null;
            }

            $baseUrl = $provider === 'github'
                ? 'https://github.com'
                : rtrim(config('services.gitlab.base_url', 'https://gitlab.com'), '/');

            return [
                'display_name' => str($identifier)->afterLast('/')->toString(),
                'name' => $identifier,
                'server_host' => null,
                'server_protocol' => null,
                'url' => $url !== '' ? $url : "{$baseUrl}/{$identifier}",
            ];
        }

        return [
            'display_name' => $name,
            'name' => $name,
            'server_host' => null,
            'server_protocol' => $provider === 'company-server' ? 'SSH' : null,
            'url' => $url !== '' ? $url : null,
        ];
    }

    protected function extractRepositoryIdentifier(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('~^(?:https?://|git@)(?:[^/:]+)[:/](.+?)(?:\.git)?/?$~i', $value, $matches)) {
            return trim($matches[1], '/');
        }

        if (preg_match('~^[^/\s]+/[^/\s]+$~', $value)) {
            return $value;
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected function parseGitHubOwnerRepo(string $name): ?array
    {
        $parts = explode('/', $name, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return [$parts[0], $parts[1]];
    }

    protected function isGitLabProjectPath(string $name): bool
    {
        $segments = array_values(array_filter(explode('/', trim($name))));

        return count($segments) >= 2;
    }
}
