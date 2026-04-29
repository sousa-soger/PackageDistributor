<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Services\GitHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class RepositoryController extends Controller
{
    // ── View ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $repositories = auth()->user()
            ->repositories()
            ->latest()
            ->get();

        return view('repositories', compact('repositories'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request, GitHubService $github)
    {
        $provider = $request->input('provider');

        // Validate based on provider
        $rules = [
            'provider' => 'required|in:github,gitlab,company-server,local-pc',
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
        ];

        match ($provider) {
            'github' => $rules += [
                'name' => 'required|string|max:255',
            ],
            'gitlab' => $rules += [
                'name' => 'required|string|max:255',
                'access_token' => 'nullable|string|max:500',
            ],
            'company-server', 'local-pc' => $rules += [
                'name' => 'required|string|max:255',
                'display_name' => 'nullable|string|max:255',
                'url' => 'nullable|url|max:500',
                'server_host' => 'nullable|string|max:255',
                'server_path' => 'nullable|string|max:500',
                'server_protocol' => 'nullable|in:SSH,SFTP,HTTP,HTTPS',
                'username' => 'nullable|string|max:255',
                'access_token' => 'nullable|string|max:500',
            ],
            default => null
        };

        $validated = $request->validate($rules);

        // Check for duplicate
        $exists = auth()->user()->repositories()
            ->where('provider', $provider)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This repository is already connected to your account.',
            ], 422);
        }

        // Try to fetch live branch/tag data for GitHub
        $branches = [];
        $tags = [];
        $defaultBranch = 'main';
        $status = 'connected';
        $externalId = null;

        if ($provider === 'github') {
            [$owner, $repo] = $this->parseOwnerRepo($validated['name']);

            $repoResponse = $github->getRepository($owner, $repo);
            if ($repoResponse->failed()) {
                return response()->json([
                    'message' => 'Could not reach this GitHub repository. Check the name and your token.',
                ], 422);
            }

            $repoData = $repoResponse->json();
            $defaultBranch = $repoData['default_branch'] ?? 'main';
            $externalId = (string) ($repoData['id'] ?? '');

            $branchesRes = $github->getBranches($owner, $repo);
            $tagsRes = $github->getTags($owner, $repo);

            if ($branchesRes->ok()) {
                $branches = collect($branchesRes->json())->pluck('name')->all();
            }
            if ($tagsRes->ok()) {
                $tags = collect($tagsRes->json())->pluck('name')->all();
            }
        }

        if ($provider === 'gitlab') {
            // If user has GitLab OAuth connected, use that token
            $token = auth()->user()->gitlab_token ?? $validated['access_token'] ?? null;
            $status = $token ? 'connected' : 'needs-auth';

            if ($token) {
                [$owner, $repo] = $this->parseOwnerRepo($validated['name']);
                $encodedPath = urlencode("{$owner}/{$repo}");
                $baseUrl = config('services.gitlab.base_url', 'https://gitlab.com');

                $repoRes = Http::withToken($token)
                    ->get("{$baseUrl}/api/v4/projects/{$encodedPath}");

                if ($repoRes->ok()) {
                    $repoData = $repoRes->json();
                    $defaultBranch = $repoData['default_branch'] ?? 'main';
                    $externalId = (string) ($repoData['id'] ?? '');

                    $branchesRes = Http::withToken($token)
                        ->get("{$baseUrl}/api/v4/projects/{$repoData['id']}/repository/branches");
                    $tagsRes = Http::withToken($token)
                        ->get("{$baseUrl}/api/v4/projects/{$repoData['id']}/repository/tags");

                    if ($branchesRes->ok()) {
                        $branches = collect($branchesRes->json())->pluck('name')->all();
                    }
                    if ($tagsRes->ok()) {
                        $tags = collect($tagsRes->json())->pluck('name')->all();
                    }
                } else {
                    $status = 'needs-auth';
                }
            }
        }

        $repository = auth()->user()->repositories()->create([
            'project_id' => $validated['project_id'] ?? null,
            'provider' => $provider,
            'name' => $validated['name'],
            'display_name' => $validated['display_name'] ?? null,
            'url' => $validated['url'] ?? null,
            'external_id' => $externalId,
            'default_branch' => $defaultBranch,
            'branches' => $branches,
            'tags' => $tags,
            'status' => $status,
            'server_host' => $validated['server_host'] ?? null,
            'server_path' => $validated['server_path'] ?? null,
            'server_protocol' => $validated['server_protocol'] ?? null,
            'username' => $validated['username'] ?? null,
            'access_token' => $validated['access_token'] ?? null,
            'last_synced_at' => now(),
        ]);

        return response()->json([
            'message' => 'Repository connected successfully.',
            'repository' => $repository,
        ], 201);
    }

    // ── Sync branches/tags from provider ─────────────────────────────────────

    public function sync(Repository $repository, GitHubService $github)
    {
        $this->authorize('update', $repository);

        if ($repository->provider === 'github') {
            [$owner, $repo] = $this->parseOwnerRepo($repository->name);

            $branchesRes = $github->getBranches($owner, $repo);
            $tagsRes = $github->getTags($owner, $repo);

            $branches = $branchesRes->ok()
                ? collect($branchesRes->json())->pluck('name')->all()
                : $repository->branches;

            $tags = $tagsRes->ok()
                ? collect($tagsRes->json())->pluck('name')->all()
                : $repository->tags;

            $repository->update([
                'branches' => $branches,
                'tags' => $tags,
                'status' => 'connected',
                'last_synced_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Repository synced.',
            'repository' => $repository->fresh(),
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Repository $repository)
    {
        $this->authorize('delete', $repository);
        $repository->delete();

        return response()->json(['message' => 'Repository removed.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function parseOwnerRepo(string $name): array
    {
        $parts = explode('/', $name, 2);

        return count($parts) === 2 ? $parts : ['', $name];
    }
}
