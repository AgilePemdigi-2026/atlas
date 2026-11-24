<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'This field is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'This field is required.',
        ]);

        try {
            $authenticated = Auth::attempt($credentials, $request->boolean('remember'));
        } catch (\Throwable) {
            return back()
                ->withErrors(['email' => 'Login cannot be processed right now.'])
                ->onlyInput('email');
        }

        if (! $authenticated) {
            $message = User::query()->where('email', $credentials['email'])->exists()
                ? 'The email address or password is incorrect.'
                : 'Invalid credentials.';

            return back()
                ->withErrors(['email' => $message])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('console'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            // 'terms' => ['accepted'],
        ], [
            'name.required' => 'This field is required.',
            'email.required' => 'This field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'This field is required.',
            'password.min' => 'Password must be at least 8 characters.',
            // 'terms.accepted' => 'Field wajib diisi.',
        ]);

        try {
            User::create(collect($data)->except('terms')->all());
        } catch (QueryException) {
            return back()
                ->withErrors(['email' => 'Registration cannot be processed right now.'])
                ->onlyInput('name', 'email');
        }

        return redirect()->route('login')->with('status', 'Your account has been created successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
