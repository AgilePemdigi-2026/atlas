<?php

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

uses(RefreshDatabase::class);

function loginInput(array $overrides = []): array
{
    return array_merge([
        'email' => 'ristulop@gmail.com',
        'password' => 'ristulristul',
    ], $overrides);
}

describe('F2 Login', function () {
    test('Login Success', function () {
        // Given I am on the "/login" page
        $this->get(route('login'))->assertOk();

        // And I fill in "Email" with "ristulop@gmail.com"
        // And I fill in "Password" with "ristulristul"
        $user = User::factory()->create([
            'email' => 'ristulop@gmail.com',
            'password' => 'ristulristul',
        ]);

        // When I press the "Login" button
        $response = $this->post(route('login'), loginInput());

        // Then I should be redirected to the "/dashboard"
        $response->assertRedirect(route('console'));

        // And a session is active
        $this->assertAuthenticatedAs($user);
    });

    test('Failed Login (missing fields)', function () {
        // Given I am on the "/login" page
        $this->get(route('login'))->assertOk();

        // And I leave the "Email" or "Password" field blank
        $payload = loginInput(['email' => '', 'password' => '']);

        // When I press the "Login" button
        $response = $this->from(route('login'))->post(route('login'), $payload);

        // Then I should see the error message "Field wajib diisi"
        $response->assertSessionHasErrors([
            'email' => 'Field wajib diisi.',
            'password' => 'Field wajib diisi.',
        ]);

        // And I should remain on the "/login" page
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });

    test('Failed Login (invalid credentials)', function () {
        // Given I am on the "/login" page
        $this->get(route('login'))->assertOk();
        User::factory()->create(['email' => 'ristulop@gmail.com']);

        // And I fill in an incorrect email or password
        $payload = loginInput(['password' => 'salah-password']);

        // When I press the "Login" button
        $response = $this->from(route('login'))->post(route('login'), $payload);

        // Then I should see the error message "Email atau password salah"
        $response->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        // And I should remain on the "/login" page
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });

    test('Failed Login (account not found)', function () {
        // Given no account exists for the email I enter
        $this->get(route('login'))->assertOk();

        // When I press the "Login" button
        $response = $this->from(route('login'))->post(route('login'), loginInput());

        // Then I should see the error message "Kredensial salah"
        $response->assertSessionHasErrors(['email' => 'Kredensial salah']);

        // And I should remain on the "/login" page
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });

    test('Failed Login (service unavailable)', function () {
        // Given the authentication service cannot be reached
        $guard = Mockery::mock(StatefulGuard::class);
        $guard->shouldReceive('check')->andReturnFalse();

        Auth::shouldReceive('guard')->andReturn($guard);
        Auth::shouldReceive('attempt')->andThrow(new RuntimeException('authentication service unavailable'));

        // When I press the "Login" button
        $response = $this->from(route('login'))->post(route('login'), loginInput());

        // Then I should see the error message "Login tidak dapat diproses saat ini"
        $response->assertSessionHasErrors(['email' => 'Login tidak dapat diproses saat ini']);

        // And I should remain on the "/login" page
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });

    test('Authenticated users are redirected away from guest-only pages', function () {
        // Given I am logged in
        $user = User::factory()->create();

        // When I open the login or register page
        // Then I am redirected away from them
        $this->actingAs($user)->get(route('login'))->assertRedirect();
        $this->actingAs($user)->get(route('register'))->assertRedirect();
    });

    test('Guests are redirected to login from protected pages', function () {
        // Given I am not logged in
        // When I open a protected page
        // Then I am redirected to the "/login" page
        $this->get(route('console'))->assertRedirect(route('login'));
        $this->get(route('profile'))->assertRedirect(route('login'));
    });
});
