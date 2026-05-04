<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\LdapService;
use App\Services\ProjectInvolvementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectInvolvementController extends Controller
{
    public function show(Request $request, Project $project, ProjectInvolvementService $involvement): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json($involvement->membersPayload($project, $request->user()));
    }

    public function storeTeam(Request $request, Project $project, ProjectInvolvementService $involvement): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'team_id' => ['required', 'integer', Rule::exists('teams', 'id')],
        ]);

        $team = $involvement->teamOptionsForUser($request->user())
            ->firstWhere('id', (int) $validated['team_id']);

        if (! $team) {
            return response()->json([
                'message' => 'Select a team you can access.',
            ], 422);
        }

        $project->teams()->syncWithoutDetaching([$team->id]);

        return response()->json($involvement->membersPayload($project->fresh(), $request->user()));
    }

    public function destroyTeam(Request $request, Project $project, Team $team, ProjectInvolvementService $involvement): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        if (! $project->teams()->whereKey($team->id)->exists()) {
            return response()->json([
                'message' => 'That team is not assigned to this project.',
            ], 404);
        }

        $project->teams()->detach($team->id);

        return response()->json($involvement->membersPayload($project->fresh(), $request->user()));
    }

    public function searchLdapUsers(Request $request, LdapService $ldap): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
        ]);

        $project = null;
        $projectMemberIds = [];

        if (! empty($validated['project_id'])) {
            $project = Project::findOrFail((int) $validated['project_id']);
            $this->authorize('manageMembers', $project);
            $projectMemberIds = $project->involvedUsers()->pluck('users.id')->all();
        }

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

        $results = $directoryUsers->map(function (array $directoryUser) use ($existingUsers, $project, $projectMemberIds) {
            $existingUser = $existingUsers->first(function (User $user) use ($directoryUser) {
                return ($directoryUser['username'] && $user->ldap_username === $directoryUser['username'])
                    || ($directoryUser['email'] && $user->email === $directoryUser['email']);
            });

            $alreadyMember = $existingUser
                ? in_array($existingUser->id, $projectMemberIds, true) || $existingUser->id === $project?->user_id
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

        return response()->json([
            'users' => $results,
        ]);
    }

    public function storeUser(Request $request, Project $project, LdapService $ldap, ProjectInvolvementService $involvement): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'role' => ['nullable', Rule::in(['member', 'viewer', 'maintainer'])],
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

        if ($member->id === $project->user_id) {
            return response()->json([
                'message' => 'The project owner is already involved in this project.',
            ], 422);
        }

        $pivot = [
            'source' => 'ldap',
            'ldap_identifier' => $directoryUser['username'] ?? $validated['username'],
            'role' => $validated['role'] ?? 'member',
        ];

        if ($project->involvedUsers()->whereKey($member->id)->exists()) {
            $project->involvedUsers()->updateExistingPivot($member->id, $pivot);
        } else {
            $project->involvedUsers()->attach($member->id, $pivot);
        }

        return response()->json($involvement->membersPayload($project->fresh(), $request->user()));
    }

    public function destroyUser(Request $request, Project $project, User $user, ProjectInvolvementService $involvement): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        if (! $project->involvedUsers()->whereKey($user->id)->exists()) {
            return response()->json([
                'message' => 'That user is not assigned to this project.',
            ], 404);
        }

        $project->involvedUsers()->detach($user->id);

        return response()->json($involvement->membersPayload($project->fresh(), $request->user()));
    }
}
