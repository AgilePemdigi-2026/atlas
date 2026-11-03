<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $email = $googleUser->getEmail();

        if (
            ! $email ||
            ! filter_var($email, FILTER_VALIDATE_EMAIL) ||
            ! ($googleUser->user['verified_email'] ?? false)
        ) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Email Google belum terverifikasi.',
                ]);
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                ]);
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: 'Google User',
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'password' => Str::random(40),
                    'email_verified_at' => now(),
                ]);
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('console'));
    }
}