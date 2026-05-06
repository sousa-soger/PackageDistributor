<?php

namespace App\Http\Controllers;

use App\Exceptions\OAuthTokenRefreshException;
use App\Models\Repository;
use App\Models\User;
use App\Services\GitHubService;
use App\Services\LdapService;
use App\Services\OAuthTokenService;
use App\Services\ProjectInvolvementService;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class RepositoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $repositories = Repository::query()
            ->where(fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhereHas('members', fn ($query) => $query->whereKey($user->id)))
            ->with('members')
            ->latest()
            ->get();

        $repositoryCards = $repositories
            ->map(fn (Repository $repository) => $this->repositoryPayload($repository, $user))
            ->values();

        $repositoryRoleOptions = $this->repositoryRoleOptions();

        $oauthConnections = [
            'github' => (bool) $user->github_token,
            'gitlab' => (bool) $user->gitlab_token,
        ];

        return view('repositories', compact('oauthConnections', 'repositories', 'repositoryCards', 'repositoryRoleOptions'));
    }

    public function store(Request $request, GitHubService $github, OAuthTokenService $oauthTokens): JsonResponse
    {
        $provider = $request->input('provider');

        $rules = [
            'access_token' => ['nullable', 'string', 'max:500'],
            'auth_method' => ['nullable', Rule::in(['oauth', 'pat'])],
            'display_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:500'],
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
            if ($validated['auth_method'] === 'oauth') {
                $metadata = $this->fetchGitHubMetadataWithOAuth(
                    $github,
                    $request->user(),
                    $oauthTokens,
                    $normalized['name']
                );
            } else {
                $metadata = $this->fetchGitHubMetadata($github, $normalized['name'], $validated['access_token']);
            }

            if (! $metadata['ok']) {
                return $this->githubConnectionErrorResponse($metadata);
            }

            $metadata['access_token'] = $validated['auth_method'] === 'pat'
                ? $validated['access_token']
                : null;
        }

        if ($validated['provider'] === 'gitlab') {
            $oauthToken = null;
            if ($validated['auth_method'] === 'oauth') {
                [$oauthToken, $authResponse] = $this->oauthTokenForProvider($request->user(), 'gitlab', $oauthTokens);
                if ($authResponse) {
                    return $authResponse;
                }
            }

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
            'project_id' => null,
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

    public function sync(Repository $repository, GitHubService $github, OAuthTokenService $oauthTokens): JsonResponse
    {
        $this->authorize('update', $repository);
        $repository->loadMissing('user');

        if ($repository->provider === 'github') {
            $metadata = $repository->access_token
                ? $this->fetchGitHubMetadata($github, $repository->name, $repository->access_token)
                : (
                    $repository->user
                        ? $this->fetchGitHubMetadataWithOAuth($github, $repository->user, $oauthTokens, $repository->name)
                        : [
                            'message' => 'This repository no longer has an owner account.',
                            'ok' => false,
                        ]
                );

            if (! $metadata['ok']) {
                if (! empty($metadata['requires_oauth'])) {
                    $repository->update(['status' => 'needs-auth']);
                }

                return $this->githubConnectionErrorResponse($metadata);
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
            $token = $repository->access_token;
            if (! $token && $repository->user) {
                [$token, $authResponse] = $this->oauthTokenForProvider($repository->user, 'gitlab', $oauthTokens);
                if ($authResponse) {
                    $repository->update(['status' => 'needs-auth']);

                    return $authResponse;
                }
            }

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

    public function versions(Request $request, Repository $repository, GitHubService $github, OAuthTokenService $oauthTokens): JsonResponse
    {
        $this->authorize('createPackage', $repository);

        if (! in_array($repository->provider, ['github', 'gitlab'], true)) {
            return response()->json([
                'message' => 'Package creation is only available for GitHub and GitLab repositories right now.',
            ], 422);
        }

        [$token, $authResponse] = $this->repositoryAccessToken($repository, $request->user(), $oauthTokens);
        if ($authResponse) {
            return $authResponse;
        }

        if ($repository->provider === 'github') {
            $parsed = $this->parseGitHubOwnerRepo($repository->name);

            if (! $parsed) {
                return response()->json([
                    'message' => 'GitHub repositories must use the format owner/repo.',
                ], 422);
            }

            [$owner, $repo] = $parsed;
            $branchesResponse = $github->getBranches($owner, $repo, $token);
            $tagsResponse = $github->getTags($owner, $repo, $token);
            $releasesResponse = $github->getReleases($owner, $repo, $token);

            if ($branchesResponse->failed() || $tagsResponse->failed() || $releasesResponse->failed()) {
                return response()->json([
                    'message' => 'Failed to fetch repository versions with the repository owner credentials.',
                ], 422);
            }

            return response()->json([
                'branches' => $branchesResponse->json() ?? [],
                'tags' => $tagsResponse->json() ?? [],
                'releases' => $releasesResponse->json() ?? [],
            ]);
        }

        $projectPath = rawurlencode($repository->external_id ?: $repository->name);
        $baseUrl = rtrim(config('services.gitlab.base_url', 'https://gitlab.com'), '/');

        $branchesResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$projectPath}/repository/branches", [
                'per_page' => 100,
            ]);

        $tagsResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v4/projects/{$projectPath}/repository/tags", [
                'per_page' => 100,
            ]);

        if ($branchesResponse->failed() || $tagsResponse->failed()) {
            return response()->json([
                'message' => 'Failed to fetch repository versions with the repository owner credentials.',
            ], 422);
        }

        return response()->json([
            'branches' => collect($branchesResponse->json())->map(fn (array $branch) => ['name' => $branch['name']])->values(),
            'tags' => collect($tagsResponse->json())->map(fn (array $tag) => ['name' => $tag['name']])->values(),
            'releases' => [],
        ]);
    }

    public function destroy(Repository $repository): JsonResponse
    {
        $this->authorize('delete', $repository);
        $repository->delete();

        return response()->json(['message' => 'Repository removed.']);
    }

    public function members(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);

        return response()->json($this->repositoryMembersPayload($repository, $request->user()));
    }

    public function searchUsers(Request $request, Repository $repository, LdapService $ldap): JsonResponse
    {
        $this->authorize('manageMembers', $repository);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $directoryUsers = collect($ldap->searchUsers($query, 8));
        $usernames = $directoryUsers->pluck('username')->filter()->values()->all();
        $emails = $directoryUsers->pluck('email')->filter()->values()->all();

        $existingUsers = empty($usernames) && empty($emails)
            ? collect()
            : User::query()
                ->where(function ($query) use ($emails, $usernames) {
                    if (! empty($usernames)) {
                        $query->whereIn('ldap_username', $usernames);
                    }

                    if (! empty($emails)) {
                        $query->orWhereIn('email', $emails);
                    }
                })
                ->get();

        $repositoryMemberIds = $repository->members()->pluck('users.id')->all();

        $results = $directoryUsers->map(function (array $directoryUser) use ($existingUsers, $repository, $repositoryMemberIds) {
            $existingUser = $existingUsers->first(function (User $user) use ($directoryUser) {
                return ($directoryUser['username'] && $user->ldap_username === $directoryUser['username'])
                    || ($directoryUser['email'] && $user->email === $directoryUser['email']);
            });

            $alreadyMember = $existingUser
                ? in_array($existingUser->id, $repositoryMemberIds, true) || $existingUser->id === $repository->user_id
                : false;

            return [
                'already_member' => $alreadyMember,
                'avatar' => $directoryUser['avatar'],
                'email' => $directoryUser['email'],
                'id' => $existingUser?->id,
                'name' => $directoryUser['name'],
                'username' => $directoryUser['username'],
            ];
        })->values();

        return response()->json(['users' => $results]);
    }

    public function storeUser(Request $request, Repository $repository, LdapService $ldap): JsonResponse
    {
        $this->authorize('manageMembers', $repository);

        $validated = $request->validate([
            'role' => ['nullable', Rule::in($this->repositoryRoleKeys())],
            'username' => ['required', 'string', 'max:255'],
        ]);

        $directoryUser = $ldap->findUser($validated['username']);

        if (! $directoryUser) {
            return response()->json([
                'message' => 'No matching LDAP user was found.',
            ], 404);
        }

        try {
            $member = $ldap->syncLocalUser($directoryUser);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($member->id === $repository->user_id) {
            return response()->json([
                'message' => 'The repository owner already has access to this repository.',
            ], 422);
        }

        $pivot = [
            'source' => 'ldap',
            'ldap_identifier' => $directoryUser['username'] ?? $validated['username'],
            'role' => $validated['role'] ?? ProjectInvolvementService::DEFAULT_PROJECT_ROLE,
        ];

        if ($repository->members()->whereKey($member->id)->exists()) {
            $repository->members()->updateExistingPivot($member->id, $pivot);
        } else {
            $repository->members()->attach($member->id, $pivot);
        }

        return response()->json($this->repositoryMembersPayload($repository->fresh(), $request->user()));
    }

    public function updateUserRole(Request $request, Repository $repository, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $repository);

        $validated = $request->validate([
            'role' => ['required', Rule::in($this->repositoryRoleKeys())],
        ]);

        if (! $repository->members()->whereKey($user->id)->exists()) {
            return response()->json([
                'message' => 'That user is not assigned to this repository.',
            ], 404);
        }

        $repository->members()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        return response()->json($this->repositoryMembersPayload($repository->fresh(), $request->user()));
    }

    public function destroyUser(Request $request, Repository $repository, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $repository);

        if (! $repository->members()->whereKey($user->id)->exists()) {
            return response()->json([
                'message' => 'That user is not assigned to this repository.',
            ], 404);
        }

        $repository->members()->detach($user->id);

        return response()->json($this->repositoryMembersPayload($repository->fresh(), $request->user()));
    }

    public function updateCredentials(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('update', $repository);

        if (in_array($repository->provider, ['github', 'gitlab'], true)) {
            $validated = $request->validate([
                'access_token' => [
                    'nullable',
                    'string',
                    'max:500',
                    Rule::requiredIf(fn () => $request->input('auth_method') === 'pat'),
                ],
                'auth_method' => ['required', Rule::in(['oauth', 'pat'])],
            ]);

            $repository->update([
                'access_token' => $validated['auth_method'] === 'pat'
                    ? $validated['access_token']
                    : null,
                'status' => 'connected',
            ]);
        } elseif ($repository->provider === 'company-server') {
            $validated = $request->validate([
                'server_host' => ['required', 'string', 'max:255'],
                'server_path' => ['required', 'string', 'max:500'],
                'server_protocol' => ['nullable', Rule::in(['SSH', 'SFTP', 'HTTP', 'HTTPS'])],
            ]);

            $repository->update([
                'name' => $validated['server_path'],
                'server_host' => $validated['server_host'],
                'server_path' => $validated['server_path'],
                'server_protocol' => $validated['server_protocol'] ?? 'SSH',
                'url' => $validated['server_path'],
                'status' => 'connected',
            ]);
        } elseif ($repository->provider === 'local-pc') {
            $validated = $request->validate([
                'server_path' => ['required', 'string', 'max:500'],
            ]);

            $repository->update([
                'name' => $validated['server_path'],
                'server_path' => $validated['server_path'],
                'url' => $validated['server_path'],
                'status' => 'connected',
            ]);
        }

        return response()->json([
            'message' => 'Repository connection updated.',
            'repository' => $this->repositoryPayload($repository->fresh(), $request->user()),
        ]);
    }

    /**
     * @return array{0: string|null, 1: JsonResponse|null}
     */
    protected function oauthTokenForProvider(User $user, string $provider, OAuthTokenService $oauthTokens): array
    {
        try {
            $token = $oauthTokens->accessToken($user, $provider);
        } catch (OAuthTokenRefreshException $e) {
            return [null, response()->json([
                'message' => $e->getMessage(),
                'redirect_url' => route("{$provider}.oauth.redirect", ['return_to' => 'repositories']),
                'requires_oauth' => true,
            ], 409)];
        }

        if (! $token) {
            return [null, response()->json([
                'message' => 'Connect your '.$this->providerLabel($provider).' account first to use OAuth.',
                'redirect_url' => route("{$provider}.oauth.redirect", ['return_to' => 'repositories']),
                'requires_oauth' => true,
            ], 409)];
        }

        return [$token, null];
    }

    /**
     * @return array{0: string|null, 1: JsonResponse|null}
     */
    protected function repositoryAccessToken(Repository $repository, User $viewer, OAuthTokenService $oauthTokens): array
    {
        if ($repository->access_token) {
            return [$repository->access_token, null];
        }

        $repository->loadMissing('user');
        $owner = $repository->user;

        if (! $owner) {
            return [null, response()->json([
                'message' => 'This repository no longer has an owner account.',
            ], 422)];
        }

        try {
            $token = $oauthTokens->accessToken($owner, $repository->provider);
        } catch (OAuthTokenRefreshException $e) {
            return [null, $this->ownerCredentialRequiredResponse($repository, $viewer, $e->getMessage())];
        }

        if (! $token) {
            return [null, $this->ownerCredentialRequiredResponse(
                $repository,
                $viewer,
                'The repository owner needs to reconnect '.$this->providerLabel($repository->provider).' OAuth or save a PAT before this repository can be packaged.'
            )];
        }

        return [$token, null];
    }

    protected function ownerCredentialRequiredResponse(Repository $repository, User $viewer, string $message): JsonResponse
    {
        $payload = ['message' => $message];

        if ($viewer->id === $repository->user_id) {
            $payload['redirect_url'] = route("{$repository->provider}.oauth.redirect", ['return_to' => 'create-package']);
            $payload['requires_oauth'] = true;

            return response()->json($payload, 409);
        }

        return response()->json($payload, 422);
    }

    protected function repositoryPayload(Repository $repository, User $viewer): array
    {
        $repository->loadMissing('members');

        $membersPayload = $this->repositoryMembersPayload($repository, $viewer);

        return array_merge($membersPayload, [
            'authType' => $this->repositoryAuthType($repository),
            'branchCount' => $repository->branch_count,
            'canCreatePackage' => $viewer->can('createPackage', $repository),
            'canManageRepository' => $viewer->can('update', $repository),
            'defaultBranch' => $repository->default_branch ?? 'main',
            'externalId' => $repository->external_id,
            'id' => $repository->id,
            'label' => $repository->label,
            'lastSyncedAt' => $repository->last_synced_at?->toIso8601String(),
            'lastSyncedLabel' => $repository->last_synced_at?->diffForHumans() ?? 'Not synced yet',
            'name' => $repository->name,
            'provider' => $repository->provider,
            'providerLabel' => $this->providerLabel($repository->provider),
            'serverHost' => $repository->server_host,
            'serverPath' => $repository->server_path,
            'serverProtocol' => $repository->server_protocol,
            'slug' => $repository->name,
            'status' => $repository->status ?? 'connected',
            'statusLabel' => $this->statusLabel($repository->status ?? 'connected'),
            'tagCount' => $repository->tag_count,
            'url' => $repository->url,
            'username' => $repository->username,
        ]);
    }

    protected function repositoryMembersPayload(Repository $repository, User $viewer): array
    {
        $repository->loadMissing('members');

        return [
            'canManageMembers' => $viewer->can('manageMembers', $repository),
            'memberCount' => $repository->members->count(),
            'users' => $repository->members
                ->sortBy('name')
                ->map(fn (User $user) => $this->memberPayload($user))
                ->values(),
        ];
    }

    protected function memberPayload(User $user): array
    {
        return [
            'avatar' => $user->avatar_url,
            'email' => $user->email,
            'id' => $user->id,
            'initials' => $this->initials($user->name ?: $user->email),
            'name' => $user->name,
            'role' => $this->normalizeRepositoryRole($user->pivot->role ?? null),
            'source' => $user->pivot->source ?? 'ldap',
            'username' => $user->display_username,
        ];
    }

    protected function repositoryRoleOptions(): array
    {
        return ProjectInvolvementService::PROJECT_ROLES;
    }

    protected function repositoryRoleKeys(): array
    {
        return array_column($this->repositoryRoleOptions(), 'key');
    }

    protected function normalizeRepositoryRole(?string $role): string
    {
        return in_array($role, $this->repositoryRoleKeys(), true)
            ? $role
            : ProjectInvolvementService::DEFAULT_PROJECT_ROLE;
    }

    protected function repositoryAuthType(Repository $repository): string
    {
        if (in_array($repository->provider, ['github', 'gitlab'], true)) {
            return $repository->access_token ? 'Personal Access Token' : 'OAuth';
        }

        if ($repository->provider === 'company-server') {
            return $repository->server_protocol ?: 'SSH';
        }

        if ($repository->provider === 'local-pc') {
            return 'Local agent';
        }

        return 'Repository connection';
    }

    protected function providerLabel(string $provider): string
    {
        return match ($provider) {
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            'company-server' => 'Company Server',
            'local-pc' => 'Local PC',
            default => ucfirst(str_replace('-', ' ', $provider)),
        };
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'connected' => 'Connected',
            'expired' => 'Expired',
            'needs-auth' => 'Needs auth',
            default => ucfirst($status),
        };
    }

    protected function initials(?string $value): string
    {
        $parts = preg_split('/\s+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '?';
        }

        return collect($parts)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }

    protected function githubConnectionErrorResponse(array $metadata): JsonResponse
    {
        $payload = [
            'message' => $metadata['message'],
        ];

        if (! empty($metadata['requires_oauth'])) {
            $payload['redirect_url'] = route('github.oauth.redirect', ['return_to' => 'repositories']);
            $payload['requires_oauth'] = true;

            return response()->json($payload, 409);
        }

        return response()->json($payload, 422);
    }

    protected function fetchGitHubMetadataWithOAuth(
        GitHubService $github,
        User $user,
        OAuthTokenService $oauthTokens,
        string $name
    ): array {
        $parsed = $this->parseGitHubOwnerRepo($name);

        if (! $parsed) {
            return [
                'message' => 'GitHub repositories must use the format owner/repo.',
                'ok' => false,
            ];
        }

        [$owner, $repo] = $parsed;

        try {
            $responses = $oauthTokens->withFreshToken(
                $user,
                'github',
                fn (string $token) => [
                    'branches' => $github->getBranches($owner, $repo, $token),
                    'repository' => $github->getRepository($owner, $repo, $token),
                    'tags' => $github->getTags($owner, $repo, $token),
                ]
            );
        } catch (OAuthTokenRefreshException $e) {
            return [
                'message' => $e->getMessage(),
                'ok' => false,
                'requires_oauth' => true,
            ];
        }

        if ($responses === null) {
            return [
                'message' => 'Connect your GitHub account first to use OAuth.',
                'ok' => false,
                'requires_oauth' => true,
            ];
        }

        return $this->githubMetadataFromResponses(
            $responses['repository'],
            $responses['branches'],
            $responses['tags'],
            true
        );
    }

    protected function fetchGitHubMetadata(GitHubService $github, string $name, ?string $token, bool $usingOAuth = false): array
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
            return $this->githubMetadataFromResponses($repoResponse, null, null, $usingOAuth);
        }

        $branchesResponse = $github->getBranches($owner, $repo, $token);
        $tagsResponse = $github->getTags($owner, $repo, $token);

        return $this->githubMetadataFromResponses($repoResponse, $branchesResponse, $tagsResponse, $usingOAuth);
    }

    protected function githubMetadataFromResponses(
        HttpResponse $repoResponse,
        ?HttpResponse $branchesResponse,
        ?HttpResponse $tagsResponse,
        bool $usingOAuth
    ): array {
        if ($repoResponse->failed()) {
            return [
                'message' => $this->githubRepositoryFailureMessage($repoResponse, $usingOAuth),
                'ok' => false,
                'requires_oauth' => $usingOAuth && $this->githubResponseNeedsOAuthReconnect($repoResponse),
            ];
        }

        $repoData = $repoResponse->json();

        return [
            'branches' => $branchesResponse?->ok()
                ? collect($branchesResponse->json())->pluck('name')->all()
                : [],
            'default_branch' => $repoData['default_branch'] ?? 'main',
            'external_id' => isset($repoData['id']) ? (string) $repoData['id'] : null,
            'message' => null,
            'ok' => true,
            'status' => 'connected',
            'tags' => $tagsResponse?->ok()
                ? collect($tagsResponse->json())->pluck('name')->all()
                : [],
        ];
    }

    protected function githubRepositoryFailureMessage(HttpResponse $response, bool $usingOAuth): string
    {
        if ($usingOAuth && $this->githubResponseMissingRepoScope($response)) {
            return 'Your GitHub OAuth token is missing the repo scope required for private repositories. Reconnect GitHub and approve private repository access, then try again.';
        }

        if ($usingOAuth && $response->unauthorized()) {
            return 'GitHub rejected the stored OAuth token. Reconnect GitHub and try again.';
        }

        return 'Could not reach this GitHub repository. Check the URL and credentials.';
    }

    protected function githubResponseNeedsOAuthReconnect(HttpResponse $response): bool
    {
        return $response->unauthorized() || $this->githubResponseMissingRepoScope($response);
    }

    protected function githubResponseMissingRepoScope(HttpResponse $response): bool
    {
        $acceptedScopes = $this->parseGitHubScopeHeader($response->header('X-Accepted-OAuth-Scopes'));
        $grantedScopes = $this->parseGitHubScopeHeader($response->header('X-OAuth-Scopes'));

        return in_array('repo', $acceptedScopes, true)
            && ! in_array('repo', $grantedScopes, true);
    }

    protected function parseGitHubScopeHeader(?string $header): array
    {
        return str($header ?? '')
            ->explode(',')
            ->map(fn (string $scope) => trim($scope))
            ->filter()
            ->values()
            ->all();
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
