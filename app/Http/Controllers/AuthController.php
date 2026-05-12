<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LdapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $incomingFields = $request->validate([
            'loginusername' => ['required', 'string'],
            'loginpassword' => 'required',
        ]);

        $username = trim($incomingFields['loginusername']);
        $password = $incomingFields['loginpassword'];

        if (config('ldap.enabled')) {
            return $this->loginWithLdap($request, $username, $password);
        }

        return $this->loginWithDatabase($request, $username, $password);
    }

    private function loginWithLdap(Request $request, string $username, string $password): RedirectResponse
    {
        $ldap = new LdapService;
        $ldapUser = $ldap->authenticate($username, $password);

        if (! $ldapUser) {
            throw ValidationException::withMessages([
                'loginpassword' => 'Invalid credentials.',
            ]);
        }

        $user = $ldap->syncLocalUser($ldapUser);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    private function loginWithDatabase(Request $request, string $username, string $password): RedirectResponse
    {
        $userExists = User::query()
            ->where('ldap_username', $username)
            ->orWhere('email', $username)
            ->exists();

        if (! $userExists) {
            throw ValidationException::withMessages([
                'loginusername' => "The LDAP username you entered isn't connected to an account.",
            ]);
        }

        if (! Auth::attempt(['ldap_username' => $username, 'password' => $password])
            && ! Auth::attempt(['email' => $username, 'password' => $password])) {
            throw ValidationException::withMessages([
                'loginpassword' => 'Incorrect password.',
            ]);
        }

        $request->session()->regenerate();

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
        Auth::logout();

        return redirect('/user-auth');
    }
}
