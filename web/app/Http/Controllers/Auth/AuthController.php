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
            'email.required' => 'Field wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Field wajib diisi.',
        ]);

        try {
            $authenticated = Auth::attempt($credentials, $request->boolean('remember'));
        } catch (\Throwable) {
            return back()
                ->withErrors(['email' => 'Login tidak dapat diproses saat ini'])
                ->onlyInput('email');
        }

        if (! $authenticated) {
            $message = User::query()->where('email', $credentials['email'])->exists()
                ? 'Email atau password salah.'
                : 'Kredensial salah';

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
            'terms' => ['accepted'],
        ], [
            'name.required' => 'Field wajib diisi.',
            'email.required' => 'Field wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar',
            'password.required' => 'Field wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'terms.accepted' => 'Field wajib diisi.',
        ]);

        try {
            User::create(collect($data)->except('terms')->all());
        } catch (QueryException) {
            return back()
                ->withErrors(['email' => 'Pendaftaran tidak dapat diproses saat ini'])
                ->onlyInput('name', 'email');
        }

        return redirect()->route('login')->with('status', 'Akun anda berhasil terdaftar');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
