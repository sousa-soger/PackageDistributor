<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LdapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function sessionStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'active' => false,
            ]);
        }

        return response()->json([
            'active' => true,
            'username' => $this->displayUsername($user),
        ]);
    }

    public function revokeCurrentSession(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'active' => false,
                'csrfToken' => csrf_token(),
            ]);
        }

        $this->logSessionTermination(
            initiatorUsername: $this->displayUsername($user),
            terminatedUsername: $this->displayUsername($user),
            terminatedUserId: $user->id,
            reason: 'login-page-conflict',
            sessionCount: 1,
        );

        $this->logoutCurrentSession($request);

        return response()->json([
            'active' => false,
            'csrfToken' => csrf_token(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $incomingFields = $request->validate([
            'loginmode' => ['required', 'in:ldap,local'],
            'loginusername' => ['required', 'string'],
            'loginpassword' => 'required',
        ]);

        $loginMode = $incomingFields['loginmode'];
        $username = trim($incomingFields['loginusername']);
        $password = $incomingFields['loginpassword'];
        $activeSessionUser = $request->user();
        $currentSessionId = $request->session()->getId();

        $user = $loginMode === 'ldap'
            ? $this->resolveLdapUser($username, $password)
            : $this->resolveLocalUser($username, $password);

        $revokedSessionCount = $this->revokeStoredSessionsForUser($user, $currentSessionId);

        if ($revokedSessionCount > 0) {
            $this->logSessionTermination(
                initiatorUsername: $this->displayUsername($user),
                terminatedUsername: $this->displayUsername($user),
                terminatedUserId: $user->id,
                reason: 'new-login-revoked-existing-sessions',
                sessionCount: $revokedSessionCount,
            );
        }

        if ($activeSessionUser) {
            $this->logSessionTermination(
                initiatorUsername: $this->displayUsername($user),
                terminatedUsername: $this->displayUsername($activeSessionUser),
                terminatedUserId: $activeSessionUser->id,
                reason: 'new-login-replaced-active-session',
                sessionCount: 1,
            );

            $this->logoutCurrentSession($request);
        }

        Auth::login($user);

        $request->session()->regenerate();

        if ($revokedSessionCount > 0 || $activeSessionUser) {
            $request->session()->flash(
                'success',
                'Previous session revoked, New session started as '.$this->displayUsername($user)
            );
        }

        return redirect()->route('home');
    }

    public function register(Request $request): RedirectResponse
    {
        $incomingFields = $request->validate([
            'name' => ['required', Rule::unique('users', 'name')],
            'email' => ['required', Rule::unique('users', 'email')],
            'password' => ['required', 'min:8'],
        ]);

        $incomingFields['password'] = bcrypt($incomingFields['password']);
        $user = User::create($incomingFields);
        Auth::login($user);

        return redirect('/');
    }

    public function logout(): RedirectResponse
    {
        $this->logoutCurrentSession(request());

        return redirect('/user-auth');
    }

    private function resolveLdapUser(string $username, string $password): User
    {
        if (! config('ldap.enabled')) {
            throw ValidationException::withMessages([
                'loginusername' => 'LDAP login is currently unavailable in this environment.',
            ]);
        }

        $ldap = new LdapService;
        $ldapUser = $ldap->authenticate($username, $password);

        if (! $ldapUser) {
            throw ValidationException::withMessages([
                'loginpassword' => 'Invalid credentials.',
            ]);
        }

        return $ldap->syncLocalUser($ldapUser);
    }

    private function resolveLocalUser(string $username, string $password): User
    {
        $user = User::query()
            ->where('ldap_username', $username)
            ->orWhere('email', $username)
            ->orWhere('name', $username)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'loginusername' => "The email or local username you entered isn't connected to an account.",
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'loginpassword' => 'Incorrect password.',
            ]);
        }

        return $user;
    }

    private function revokeStoredSessionsForUser(User $user, string $exceptSessionId): int
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $exceptSessionId)
            ->delete();
    }

    private function logoutCurrentSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function logSessionTermination(
        string $initiatorUsername,
        string $terminatedUsername,
        int $terminatedUserId,
        string $reason,
        int $sessionCount,
    ): void {
        Log::info(
            sprintf(
                'Session terminated — new login initiated by %s at %s',
                $initiatorUsername,
                now()->toIso8601String(),
            ),
            [
                'terminated_user_id' => $terminatedUserId,
                'terminated_username' => $terminatedUsername,
                'reason' => $reason,
                'revoked_session_count' => $sessionCount,
            ]
        );
    }

    private function displayUsername(User $user): string
    {
        return $user->ldap_username
            ?: $user->email
            ?: $user->name;
    }
}
