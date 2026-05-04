<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProjectInvolvementService
{
    public function visibleProjectsFor(User $user): Builder
    {
        $teamIds = $user->teams()->pluck('teams.id');

        return Project::query()
            ->where(function (Builder $query) use ($teamIds, $user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('involvedUsers', fn (Builder $query) => $query->whereKey($user->id));

                if ($teamIds->isNotEmpty()) {
                    $query->orWhereHas('teams', fn (Builder $query) => $query->whereIn('teams.id', $teamIds));
                }
            });
    }

    public function projectCardPayload(Project $project, User $viewer, ?Collection $teamOptions = null): array
    {
        $this->loadProjectInvolvement($project);

        $canManageMembers = $viewer->can('manageMembers', $project);

        return [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'description' => $project->description ?: 'No description added yet.',
            'color' => $project->color ?: Project::DEFAULT_COLOR,
            'repoCount' => $project->repositories->count(),
            'lastDeployedAt' => $project->last_deployed_at?->diffForHumans() ?? '-',
            'canManageMembers' => $canManageMembers,
            'canManageProject' => $viewer->can('update', $project),
            'memberCount' => $this->involvedCount($project),
            'teams' => $this->teamsPayload($project->teams),
            'users' => $this->usersPayload($project->involvedUsers),
            'availableTeams' => $canManageMembers
                ? $this->availableTeamsPayload($project, $viewer, $teamOptions)
                : [],
            'repositories' => $project->repositories->map(fn ($repository) => [
                'id' => $repository->id,
                'name' => $repository->display_name ?? $repository->name,
                'provider' => $repository->provider,
                'defaultBranch' => $repository->default_branch ?? 'main',
                'branchCount' => count($repository->branches ?? []),
                'tagCount' => count($repository->tags ?? []),
                'status' => $repository->status ?? 'connected',
            ])->values(),
        ];
    }

    public function membersPayload(Project $project, User $viewer): array
    {
        $this->loadProjectInvolvement($project);

        $canManageMembers = $viewer->can('manageMembers', $project);

        return [
            'canManageMembers' => $canManageMembers,
            'memberCount' => $this->involvedCount($project),
            'teams' => $this->teamsPayload($project->teams),
            'users' => $this->usersPayload($project->involvedUsers),
            'availableTeams' => $canManageMembers
                ? $this->availableTeamsPayload($project, $viewer)
                : [],
        ];
    }

    public function teamOptionsForUser(User $user): Collection
    {
        return Team::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('owner_user_id', $user->id)
                    ->orWhereHas('members', fn (Builder $query) => $query->whereKey($user->id));
            })
            ->withCount('members')
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();
    }

    public function teamsPayload(Collection $teams): Collection
    {
        return $teams->map(fn (Team $team) => [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'initials' => $this->initials($team->name),
            'memberCount' => (int) ($team->members_count ?? $team->members()->count()),
        ])->values();
    }

    public function usersPayload(Collection $users): Collection
    {
        return $users->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->display_username,
            'avatar' => $user->avatar_url,
            'initials' => $this->initials($user->name ?: $user->email),
            'role' => $user->pivot->role ?? 'member',
            'source' => $user->pivot->source ?? 'ldap',
        ])->values();
    }

    protected function availableTeamsPayload(Project $project, User $viewer, ?Collection $teamOptions = null): Collection
    {
        $teamOptions ??= $this->teamOptionsForUser($viewer);
        $linkedTeamIds = $project->teams->pluck('id')->all();

        return $this->teamsPayload(
            $teamOptions->reject(fn (Team $team) => in_array($team->id, $linkedTeamIds, true))->values()
        );
    }

    protected function loadProjectInvolvement(Project $project): void
    {
        $project->loadMissing([
            'repositories',
            'teams' => fn ($query) => $query->withCount('members')->orderBy('name'),
            'involvedUsers' => fn ($query) => $query->orderBy('name'),
        ]);
    }

    protected function involvedCount(Project $project): int
    {
        return $project->teams->count() + $project->involvedUsers->count();
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
}
