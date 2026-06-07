<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('vdj.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $data['login'], 'password' => $data['password']], $request->boolean('remember'))) {
            return back()
                ->withErrors(['login' => 'These credentials do not match our records.'])
                ->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function showRegister()
    {
        return view('vdj.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'alpha_dash', 'min:3', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password'],
            'is_onboarded' => false,
            'is_admin' => false,
            'is_verified' => false,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
