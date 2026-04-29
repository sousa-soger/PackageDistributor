<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\LdapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public static array $roles = [
        ['key' => 'owner', 'label' => 'Owner', 'desc' => 'Full control, billing, role configuration'],
        ['key' => 'maintainer', 'label' => 'Maintainer', 'desc' => 'Configure projects, repositories and policies'],
        ['key' => 'creator', 'label' => 'Package Creator', 'desc' => 'Create packages, cannot deploy to PROD'],
        ['key' => 'deployer', 'label' => 'Deployer', 'desc' => 'Deploy approved packages to permitted environments'],
        ['key' => 'viewer', 'label' => 'Viewer', 'desc' => 'Read-only access to packages and deployments'],
    ];

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensurePersonalTeam($user);

        $teams = $user->teams()
            ->withCount('members')
            ->orderBy('name')
            ->get();

        $selectedTeam = $this->resolveCurrentTeam($request, $teams);

        $currentTeam = $user->teams()
            ->whereKey($selectedTeam->id)
            ->withCount('members')
            ->with([
                'members' => fn ($query) => $query->orderBy('name'),
                'projects' => fn ($query) => $query->withCount('repositories')->orderBy('name'),
            ])
            ->firstOrFail();

        $members = $currentTeam->members
            ->sortBy(fn (User $member) => sprintf(
                '%02d-%s',
                $this->roleWeight($member->pivot->role ?? 'viewer'),
                strtolower($member->name)
            ))
            ->values();

        $memberIds = $currentTeam->members->pluck('id')->all();

        $teamProjects = $currentTeam->projects
            ->map(function (Project $project) {
                $project->repoCount = $project->repositories_count;

                return $project;
            })
            ->values();

        $availableProjects = Project::query()
            ->whereIn('user_id', $memberIds)
            ->whereDoesntHave('teams', fn ($query) => $query->where('teams.id', $currentTeam->id))
            ->withCount('repositories')
            ->orderBy('name')
            ->get();

        $currentUserRole = $this->membershipRole($currentTeam, $user);
        $canManageTeam = in_array($currentUserRole, ['owner', 'maintainer'], true);

        return view('team', [
            'availableProjects' => $availableProjects,
            'canManageTeam' => $canManageTeam,
            'currentTeam' => $currentTeam,
            'currentUserRole' => $currentUserRole,
            'members' => $members,
            'roles' => self::$roles,
            'teamProjects' => $teamProjects,
            'teams' => $teams,
        ]);
    }

    public function searchDirectoryUsers(Request $request, LdapService $ldap): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')],
        ]);

        $query = trim((string) $request->input('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        /** @var User $user */
        $user = $request->user();
        $team = null;
        $teamMemberIds = [];

        if ($request->filled('team_id')) {
            $team = Team::findOrFail((int) $request->input('team_id'));
            $this->authorizeTeamAdmin($team, $user);
            $teamMemberIds = $team->members()->pluck('users.id')->all();
        }

        $directoryUsers = collect($ldap->searchUsers($query, 8));
        $usernames = $directoryUsers->pluck('username')->filter()->values()->all();
        $emails = $directoryUsers->pluck('email')->filter()->values()->all();
        $existingUsers = empty($usernames) && empty($emails)
            ? collect()
            : User::query()
                ->where(function ($query) use ($usernames, $emails) {
                    if (! empty($usernames)) {
                        $query->whereIn('ldap_username', $usernames);
                    }

                    if (! empty($emails)) {
                        $query->orWhereIn('email', $emails);
                    }
                })
                ->get();

        $results = $directoryUsers->map(function (array $directoryUser) use ($existingUsers, $teamMemberIds) {
            $existingUser = $existingUsers->first(function (User $user) use ($directoryUser) {
                return ($directoryUser['username'] && $user->ldap_username === $directoryUser['username'])
                    || ($directoryUser['email'] && $user->email === $directoryUser['email']);
            });

            return [
                'already_member' => $existingUser ? in_array($existingUser->id, $teamMemberIds, true) : false,
                'avatar' => $directoryUser['avatar'],
                'email' => $directoryUser['email'],
                'id' => $existingUser?->id,
                'name' => $directoryUser['name'],
                'username' => $directoryUser['username'],
            ];
        })->values();

        return response()->json([
            'users' => $results,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $team = Team::create([
            'owner_user_id' => $user->id,
            'name' => $request->string('name')->trim()->toString(),
            'slug' => $this->uniqueTeamSlug($request->input('slug') ?: $request->input('name')),
        ]);

        $team->members()->attach($user->id, [
            'invited_by_user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        return redirect()
            ->route('team', ['team' => $team->id])
            ->with('success', 'Team created successfully.');
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->authorizeTeamAdmin($team, $user);

        $team->update([
            'name' => $request->string('name')->trim()->toString(),
            'slug' => $this->uniqueTeamSlug($request->input('slug') ?: $request->input('name'), $team->id),
        ]);

        return redirect()
            ->route('team', ['team' => $team->id])
            ->with('success', 'Team details updated.');
    }

    public function invite(Request $request, Team $team, LdapService $ldap): RedirectResponse
    {
        $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
            ],
            'role' => ['required', Rule::in(array_column(self::$roles, 'key'))],
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->authorizeTeamAdmin($team, $user);

        $directoryUser = $ldap->findUser($request->input('username'));

        if (! $directoryUser) {
            return redirect()
                ->route('team', ['team' => $team->id])
                ->withInput()
                ->with('error', 'No matching LDAP user was found for that username.');
        }

        try {
            $invitee = $ldap->syncLocalUser($directoryUser);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('team', ['team' => $team->id])
                ->withInput()
                ->with('error', $e->getMessage());
        }

        if ($invitee->is($user)) {
            return redirect()
                ->route('team', ['team' => $team->id])
                ->with('error', 'You are already on this team.');
        }

        if ($team->members()->whereKey($invitee->id)->exists()) {
            $team->members()->updateExistingPivot($invitee->id, [
                'invited_by_user_id' => $user->id,
                'role' => $request->input('role'),
                'status' => 'active',
            ]);

            return redirect()
                ->route('team', ['team' => $team->id])
                ->with('success', "{$invitee->name}'s team role was updated.");
        }

        $team->members()->attach($invitee->id, [
            'invited_by_user_id' => $user->id,
            'role' => $request->input('role'),
            'status' => 'active',
        ]);

        return redirect()
            ->route('team', ['team' => $team->id])
            ->with('success', "{$invitee->name} was added to the team.");
    }

    public function updateRole(Request $request, Team $team, User $member): RedirectResponse
    {
        $request->validate([
            'role' => ['required', Rule::in(array_column(self::$roles, 'key'))],
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->authorizeTeamAdmin($team, $user);
        $this->ensureMemberExistsOnTeam($team, $member);

        if ($member->is($user)) {
            return redirect()
                ->route('team', ['team' => $team->id])
                ->with('error', 'You cannot change your own role here.');
        }

        $actorRole = $this->membershipRole($team, $user);
        $currentRole = $this->membershipRole($team, $member);
        $newRole = $request->input('role');

        if ($currentRole === 'owner' && $actorRole !== 'owner') {
            abort(403, 'Only team owners can change another owner.');
        }

        if ($newRole === 'owner' && $actorRole !== 'owner') {
            abort(403, 'Only team owners can promote another owner.');
        }

        if ($currentRole === 'owner' && $newRole !== 'owner' && $this->ownerCount($team) <= 1) {
            return redirect()
                ->route('team', ['team' => $team->id])
                ->with('error', 'Every team needs at least one owner.');
        }

        $team->members()->updateExistingPivot($member->id, [
            'role' => $newRole,
        ]);

        return redirect()
            ->route('team', ['team' => $team->id])
            ->with('success', "{$member->name}'s role updated to " . ucfirst($newRole) . '.');
    }

    public function removeMember(Request $request, Team $team, User $member): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeTeamAdmin($team, $user);
        $this->ensureMemberExistsOnTeam($team, $member);

        if ($member->is($user)) {
            return redirect()
                ->route('team', ['team' => $team->id])
                ->with('error', 'You cannot remove yourself.');
        }

        if ($this->membershipRole($team, $member) === 'owner') {
            return redirect()
                ->route('team', ['team' => $team->id])
                ->with('error', 'Transfer ownership before removing an owner.');
        }

        $team->members()->detach($member->id);

        return redirect()
            ->route('team', ['team' => $team->id])
            ->with('success', "{$member->name} has been removed from the team.");
    }

    public function assignProject(Request $request, Team $team): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeTeamAdmin($team, $user);

        $memberIds = $team->members()->pluck('users.id')->all();

        $request->validate([
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query->whereIn('user_id', $memberIds)
                ),
            ],
        ]);

        $projectId = (int) $request->input('project_id');

        if ($team->projects()->whereKey($projectId)->exists()) {
            return redirect()
                ->route('team', ['team' => $team->id])
                ->with('error', 'That project is already assigned to this team.');
        }

        $team->projects()->attach($projectId);

        $project = Project::findOrFail($projectId);

        return redirect()
            ->route('team', ['team' => $team->id])
            ->with('success', "{$project->name} was assigned to the team.");
    }

    public function removeProject(Request $request, Team $team, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeTeamAdmin($team, $user);

        if (! $team->projects()->whereKey($project->id)->exists()) {
            return redirect()
                ->route('team', ['team' => $team->id])
                ->with('error', 'That project is not assigned to this team.');
        }

        $team->projects()->detach($project->id);

        return redirect()
            ->route('team', ['team' => $team->id])
            ->with('success', "{$project->name} was removed from the team.");
    }

    protected function authorizeTeamAdmin(Team $team, User $user): void
    {
        abort_unless(
            in_array($this->membershipRole($team, $user), ['owner', 'maintainer'], true),
            403,
            'Only team owners and maintainers can manage this team.'
        );
    }

    protected function ensureMemberExistsOnTeam(Team $team, User $member): void
    {
        abort_unless(
            $team->members()->whereKey($member->id)->exists(),
            404,
            'That user is not a member of this team.'
        );
    }

    protected function ensurePersonalTeam(User $user): void
    {
        if ($user->teams()->exists()) {
            return;
        }

        $team = Team::create([
            'owner_user_id' => $user->id,
            'name' => "{$user->name}'s Team",
            'slug' => $this->uniqueTeamSlug("{$user->name} team"),
        ]);

        $team->members()->attach($user->id, [
            'invited_by_user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    protected function resolveCurrentTeam(Request $request, Collection $teams): Team
    {
        $selectedTeamId = (int) $request->query('team', 0);

        $selectedTeam = $teams->firstWhere('id', $selectedTeamId) ?? $teams->first();

        abort_unless($selectedTeam instanceof Team, 404, 'No team could be resolved for this user.');

        return $selectedTeam;
    }

    protected function membershipRole(Team $team, User $user): ?string
    {
        if ($team->relationLoaded('members')) {
            $member = $team->members->firstWhere('id', $user->id);

            return $member?->pivot?->role;
        }

        $member = $team->members()->whereKey($user->id)->first();

        return $member?->pivot?->role;
    }

    protected function ownerCount(Team $team): int
    {
        return $team->members()
            ->wherePivot('role', 'owner')
            ->count();
    }

    protected function roleWeight(string $role): int
    {
        return match ($role) {
            'owner' => 0,
            'maintainer' => 1,
            'creator' => 2,
            'deployer' => 3,
            default => 4,
        };
    }

    protected function uniqueTeamSlug(string $value, ?int $ignoreTeamId = null): string
    {
        $seed = Str::slug($value);
        $seed = $seed !== '' ? $seed : 'team';
        $slug = $seed;
        $counter = 2;

        while (
            Team::query()
                ->when($ignoreTeamId, fn ($query) => $query->where('id', '!=', $ignoreTeamId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $seed.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
