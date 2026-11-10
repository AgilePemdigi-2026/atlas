<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// F4 - Forgot Password
describe('F4 Forgot Password', function () {
    test('Forgot Password', function () {
        // Given I am on the "/login" page
        $login = $this->get('/login')->assertOk();
        // When I click on the "Lupa Password" link
        $login->assertSee('<a href="'.route('password.request'), false);
        // Then I should be redirected to the "/forgot-password" page
        $this->get('/forgot-password')->assertOk()->assertSee('Email');
    });

    test('Reset password via email', function () {
        Notification::fake();

        $user = User::factory()->create();

        // Given I am on the reset password page (post the reset request first)
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', 'Link reset password berhasil diproses. Silakan cek email.');
        // When I submit my registered email
        // Then a reset link is sent to that email
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            fn (ResetPassword $notification) => is_string($notification->token) && $notification->token !== '',
        );

        $token = Notification::sent($user, ResetPassword::class)->first()->token;
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
        // And I am sent back to the "/login" page after resetting
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Password-Reset-2026',
            'password_confirmation' => 'Password-Reset-2026',
        ])->assertRedirect(route('login'));

        expect(Hash::check('Password-Reset-2026', $user->fresh()->password))->toBeTrue();
    });
});
