<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login (Request $request){
        $incomingFields = $request->validate([
            'loginemail' => 'required',
            'loginpassword' => 'required'
        ]);

        // checks if email exists
        $userExists = User::where('email', $incomingFields['loginemail'])->exists();

        if (! $userExists) {
            throw ValidationException::withMessages([
                'loginemail' => "The email address or mobile number you entered isn't connected to an account.",
            ]);
        }

        if (! Auth::attempt([
            'email' => $incomingFields['loginemail'],
            'password' => $incomingFields['loginpassword'],
        ])) {
            throw ValidationException::withMessages([
                'loginpassword' => 'Incorrect password.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('home');
    }



    //'name' => ['required', Rule::unique('users', 'name')], is better for scalability compared to 'name' => 'required|unique:users',
    public function register(Request $request){
        $incomingFields = $request->validate([
            'name' => ['required', Rule::unique('users', 'name')],
            'email'=> ['required', Rule::unique('users','email')],
            'password'=> ['required', 'min:8']     
        ]);
        

        $incomingFields['password'] = bcrypt($incomingFields['password']);
        $user = User::create($incomingFields);
        //auth()->login() or Auth::login()
        Auth::login($user);
        return redirect('/');
    }

    public function logout(){
        //auth()->logout() or Auth::logout()
        Auth::logout();
        return redirect('/user-auth');
    }
}
