<?php

namespace App\Http\Controllers;

use App\Models\DeploymentJob;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PackageController extends Controller
{
    public function indexV3(Request $request)
    {
        $vcsProvider = config('packaging.vcs_provider', 'github');
        $user = $request->user();
        $gitlabConnected = filled($user->gitlab_token);

        // Only needed for the GitHub dropdown; GitLab fetches dynamically.
        $repositories = $vcsProvider === 'github'
            ? array_map(static function (array $item) {
                return [
                    'id' => $item['id'],
                    'label' => $item['label'],
                    'owner' => $item['owner'],
                    'repo' => $item['repo'],
                ];
            }, config('github-repos'))
            : [];

        $packages = DeploymentJob::where('user_id', auth()->id())
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        $queuedPackages = DeploymentJob::where('user_id', auth()->id())
            ->whereIn('status', ['running', 'queued', 'pending'])
            ->orderByDesc('created_at')
            ->get();

        return view('new-packageV3', compact('repositories', 'packages', 'queuedPackages', 'vcsProvider', 'gitlabConnected'));
    }

    public function createPackage(Request $request): View
    {
        $vcsProvider = config('packaging.vcs_provider', 'github');
        $user = $request->user();
        $gitlabConnected = filled($user->gitlab_token);

        $repositories = $this->packageableRepositoriesFor($user)
            ->map(fn (Repository $repository) => $this->repositoryOption($repository, $user))
            ->values();

        $requestedRepositoryId = (int) $request->query('repository');
        $selectedRepositoryId = $repositories->contains('id', $requestedRepositoryId)
            ? $requestedRepositoryId
            : null;

        $packages = DeploymentJob::where('user_id', auth()->id())
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        $queuedPackages = DeploymentJob::where('user_id', auth()->id())
            ->whereIn('status', ['running', 'queued', 'pending'])
            ->orderByDesc('created_at')
            ->get();

        return view('create-package', compact('repositories', 'packages', 'queuedPackages', 'vcsProvider', 'gitlabConnected', 'selectedRepositoryId'));
    }

    public function donePackages()
    {
        $packages = DeploymentJob::where('user_id', auth()->id())
            ->where('message', 'Done')
            ->orderByDesc('created_at')
            ->get();

        return view('packages.done', compact('packages'));
    }

    public function packages(Request $request)
    {
        $packages = DeploymentJob::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('packages', compact('packages'));
    }

    public function queuedPackages()
    {
        $queuedPackages = DeploymentJob::where('user_id', auth()->id())
            ->whereIn('status', ['running', 'queued', 'pending'])
            ->orderByDesc('created_at')
            ->get();

        return view('packages.queued', compact('queuedPackages'));
    }

    private function packageableRepositoriesFor(User $user): Collection
    {
        return Repository::query()
            ->whereIn('provider', ['github', 'gitlab'])
            ->where('status', 'connected')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('members', fn ($query) => $query
                        ->whereKey($user->id)
                        ->whereIn('repository_user.role', Repository::PACKAGE_CREATOR_ROLES));
            })
            ->with([
                'user',
                'members' => fn ($query) => $query->whereKey($user->id),
            ])
            ->latest()
            ->get()
            ->filter(fn (Repository $repository) => $user->can('createPackage', $repository))
            ->values();
    }

    private function repositoryOption(Repository $repository, User $user): array
    {
        $memberRole = $repository->members->first()?->pivot?->role;

        return [
            'id' => $repository->id,
            'label' => $repository->label,
            'name' => $repository->name,
            'provider' => $repository->provider,
            'providerLabel' => $this->providerLabel($repository->provider),
            'defaultBranch' => $repository->default_branch ?? 'main',
            'branchCount' => $repository->branch_count,
            'tagCount' => $repository->tag_count,
            'ownerName' => $repository->user?->name ?: $repository->user?->email,
            'role' => $repository->user_id === $user->id ? 'owner' : $memberRole,
            'url' => $repository->url,
        ];
    }

    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            default => ucfirst(str_replace('-', ' ', $provider)),
        };
    }
}
