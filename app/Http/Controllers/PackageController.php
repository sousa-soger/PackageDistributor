<?php

namespace App\Http\Controllers;

use App\Models\DeploymentJob;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

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

    public function packages(Request $request): View
    {
        $user = $request->user();

        $visibleRepositoryIds = Repository::query()
            ->where(fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhereHas('members', fn ($query) => $query->whereKey($user->id)))
            ->pluck('id');

        $packages = DeploymentJob::query()
            ->with([
                'creator',
                'repository.user',
                'repository.members' => fn ($query) => $query->orderBy('name'),
            ])
            ->where(function ($query) use ($user, $visibleRepositoryIds) {
                $query->where('user_id', $user->id);

                if ($visibleRepositoryIds->isNotEmpty()) {
                    $query->orWhereIn('repository_id', $visibleRepositoryIds);
                }
            })
            ->orderByDesc('created_at')
            ->get();

        $packageGroups = $this->packageRepositoryGroups($packages);
        $repositoryFilters = $packageGroups
            ->map(fn (array $group) => [
                'key' => $group['key'],
                'name' => $group['name'],
            ])
            ->values();
        $creatorFilters = $packages
            ->pluck('creator')
            ->filter()
            ->unique('id')
            ->sortBy(fn (User $creator) => $creator->name ?: $creator->email)
            ->map(fn (User $creator) => $this->personPayload($creator))
            ->values();
        $repositoryClientIndex = $this->repositoryClientIndex($packageGroups);
        $packageClientIndex = $this->packageClientIndex($packageGroups);
        $packagePermissions = $this->packagePermissions($packages, $user);

        return view('packages', compact(
            'creatorFilters',
            'packageClientIndex',
            'packageGroups',
            'packagePermissions',
            'packages',
            'repositoryClientIndex',
            'repositoryFilters'
        ));
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
            ->whereIn('provider', ['github', 'gitlab', 'local-pc'])
            ->where('status', 'connected')
            ->where(function ($query) {
                $query->whereIn('provider', ['github', 'gitlab'])
                    ->orWhere(function ($query) {
                        $query->where('provider', 'local-pc')
                            ->whereIn('type', ['ssh-mirror', 'uploaded'])
                            ->whereNotNull('storage_path');
                    });
            })
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
            ->filter(fn (Repository $repository) => $user->can('createPackage', $repository)
                && ($repository->provider !== 'local-pc' || (filled($repository->storage_path) && File::isDirectory($repository->storage_path))))
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
            'type' => $repository->type,
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
            'local-pc' => 'Local Repository',
            default => ucfirst(str_replace('-', ' ', $provider)),
        };
    }

    private function packageRepositoryGroups(Collection $packages): Collection
    {
        return $packages
            ->groupBy(fn (DeploymentJob $package): string => $this->packageRepositoryGroupKey($package))
            ->map(function (Collection $packages, string $key): array {
                /** @var DeploymentJob $firstPackage */
                $firstPackage = $packages->first();
                $repository = $firstPackage->repository;
                $owner = $repository?->user;
                $ownerName = $owner?->name ?: $owner?->email;
                $fallbackOwner = $firstPackage->creator?->name ?: $firstPackage->creator?->email;
                $repositoryName = $repository?->label ?: ($firstPackage->repo ?: $firstPackage->project_name);

                $contributors = collect();

                if ($owner) {
                    $contributors->push($this->personPayload($owner, 'Owner'));
                }

                if ($repository) {
                    $repository->members
                        ->each(fn (User $member) => $contributors->push(
                            $this->personPayload($member, $this->roleLabel($member->pivot->role ?? null))
                        ));
                }

                $packages
                    ->pluck('creator')
                    ->filter()
                    ->each(fn (User $creator) => $contributors->push($this->personPayload($creator, 'Package Creator')));

                $contributors = $contributors
                    ->unique('id')
                    ->values();

                return [
                    'key' => $key,
                    'name' => $repositoryName ?: 'Unassigned repository',
                    'ownerName' => $ownerName ?: ($fallbackOwner ?: 'Unknown owner'),
                    'ownerInitials' => $this->initials($ownerName ?: $fallbackOwner),
                    'contributors' => $contributors,
                    'contributorCount' => $contributors->count(),
                    'packages' => $packages->values(),
                ];
            })
            ->values();
    }

    private function packageRepositoryGroupKey(DeploymentJob $package): string
    {
        if ($package->repository_id) {
            return "repository-{$package->repository_id}";
        }

        return 'legacy-'.md5((string) ($package->repo ?: $package->project_name ?: $package->id));
    }

    private function repositoryClientIndex(Collection $packageGroups): array
    {
        return $packageGroups
            ->mapWithKeys(fn (array $group) => [
                $group['key'] => [
                    'packageIds' => $group['packages']->pluck('id')->values()->all(),
                ],
            ])
            ->all();
    }

    private function packageClientIndex(Collection $packageGroups): array
    {
        return $packageGroups
            ->mapWithKeys(fn (array $group) => $group['packages']
                ->mapWithKeys(function (DeploymentJob $package) use ($group) {
                    $creatorName = $package->creator?->name ?: $package->creator?->email;

                    return [
                        $package->id => [
                            'repositoryKey' => $group['key'],
                            'creatorId' => $package->user_id ? (string) $package->user_id : '',
                            'search' => strtolower(implode(' ', array_filter([
                                $package->package_name,
                                $package->repo,
                                $package->project_name,
                                $package->environment,
                                $package->base_version,
                                $package->head_version,
                                $group['name'],
                                $group['ownerName'],
                                $creatorName,
                            ]))),
                        ],
                    ];
                })
                ->all())
            ->all();
    }

    private function packagePermissions(Collection $packages, User $viewer): array
    {
        return $packages
            ->mapWithKeys(fn (DeploymentJob $package) => [
                $package->id => [
                    'canDelete' => $viewer->can('delete', $package),
                    'canDeploy' => $viewer->can('deploy', $package),
                ],
            ])
            ->all();
    }

    private function personPayload(User $user, ?string $role = null): array
    {
        $name = $user->name ?: $user->email ?: 'Unknown user';

        return [
            'avatar' => $user->avatar_url,
            'id' => $user->id,
            'initials' => $this->initials($name),
            'name' => $name,
            'role' => $role,
        ];
    }

    private function roleLabel(?string $role): string
    {
        return match ($role) {
            'maintainer' => 'Maintainer',
            'creator' => 'Package Creator',
            'deployer' => 'Deployer',
            'viewer' => 'Viewer',
            default => 'Contributor',
        };
    }

    private function initials(?string $value): string
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
}
