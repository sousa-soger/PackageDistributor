<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TeamController extends Controller
{
    /**
     * The roles definition — single source of truth.
     */
    public static array $roles = [
        ['key' => 'owner',      'label' => 'Owner',          'desc' => 'Full control, billing, role configuration'],
        ['key' => 'maintainer', 'label' => 'Maintainer',     'desc' => 'Configure projects, repositories and policies'],
        ['key' => 'creator',    'label' => 'Package Creator','desc' => 'Create packages, cannot deploy to PROD'],
        ['key' => 'deployer',   'label' => 'Deployer',       'desc' => 'Deploy approved packages to permitted environments'],
        ['key' => 'viewer',     'label' => 'Viewer',         'desc' => 'Read-only access to packages and deployments'],
    ];

    // ── View ─────────────────────────────────────────────────────────────────

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // For now: treat the authed user's own team as a flat member list on the user model.
        // When you add a Team model later, swap this for $user->currentTeam->members
        $members = User::whereIn('id', $this->teamMemberIds($user))
            ->get()
            ->map(function (User $u) use ($user) {
                // Fake pivot data until a team_user pivot table exists
                $u->pivot = (object) [
                    'role'   => $u->id === $user->id ? 'owner' : ($u->team_role ?? 'viewer'),
                    'status' => $u->team_status ?? 'active',
                ];
                return $u;
            });

        return view('team', [
            'members'     => $members,
            'roles'       => self::$roles,
            'currentTeam' => null, // replace with $user->currentTeam when Team model exists
        ]);
    }

    // ── Invite ────────────────────────────────────────────────────────────────

    public function invite(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role'  => ['required', 'in:owner,maintainer,creator,deployer,viewer'],
        ]);

        // Check if user already exists
        $invitee = User::where('email', $request->email)->first();

        if ($invitee) {
            // User exists — add them directly (pending acceptance)
            // When you have a team_user pivot: $currentTeam->members()->attach($invitee->id, ['role' => $request->role, 'status' => 'pending'])
            // For now we just set a flag on the user
            $invitee->update([
                'team_role'   => $request->role,
                'team_status' => 'pending',
            ]);
        }

        // TODO: Send invitation email
        // Mail::to($request->email)->send(new TeamInviteMail(auth()->user(), $request->role));

        return redirect()->route('team')
            ->with('success', "Invite sent to {$request->email}.");
    }

    // ── Update role ───────────────────────────────────────────────────────────

    public function updateRole(Request $request, User $member)
    {
        $this->authorizeTeamAdmin();

        $request->validate([
            'role' => ['required', 'in:owner,maintainer,creator,deployer,viewer'],
        ]);

        // When you have pivot: $currentTeam->members()->updateExistingPivot($member->id, ['role' => $request->role]);
        $member->update(['team_role' => $request->role]);

        return redirect()->route('team')
            ->with('success', "{$member->name}'s role updated to " . ucfirst($request->role) . '.');
    }

    // ── Remove member ─────────────────────────────────────────────────────────

    public function removeMember(User $member)
    {
        $this->authorizeTeamAdmin();

        if ($member->id === auth()->id()) {
            return redirect()->route('team')->with('error', 'You cannot remove yourself.');
        }

        // When you have pivot: $currentTeam->members()->detach($member->id);
        $member->update(['team_role' => null, 'team_status' => null]);

        return redirect()->route('team')
            ->with('success', "{$member->name} has been removed from the team.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function authorizeTeamAdmin(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        abort_unless(
            in_array($user->team_role ?? 'owner', ['owner', 'maintainer']),
            403,
            'Only team owners and maintainers can manage members.'
        );
    }

    /**
     * Placeholder: return IDs of all users in the current team.
     * Replace with proper team relationship once Team model exists.
     */
    protected function teamMemberIds(User $user): array
    {
        // Always include self
        return array_unique(array_merge(
            [$user->id],
            User::whereNotNull('team_role')->pluck('id')->toArray()
        ));
    }
}
