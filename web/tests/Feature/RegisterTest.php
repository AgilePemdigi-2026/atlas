<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

uses(RefreshDatabase::class);

function registerInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rizztul',
        'email' => 'ristulop@gmail.com',
        'password' => 'ristulristul',
        'terms' => '1',
    ], $overrides);
}

describe('F1 Register', function () {
    test('Register Success', function () {
        // Given I am on the "/register" page
        $this->get(route('register'))->assertOk();

        // And I fill in "Name" with "Rizztul"
        // And I fill in "Email" with "ristulop@gmail.com"
        // And I fill in "Password" with "ristulristul"
        $payload = registerInput();

        // When I press the "Register" button
        $response = $this->post(route('register'), $payload);

        // Then I should see the message "Akun anda berhasil terdaftar"
        $response->assertSessionHas('status', 'Akun anda berhasil terdaftar');

        // And I should be redirected to the "/login" page
        $response->assertRedirect(route('login'));

        // And the account is saved
        expect(User::query()->where('email', 'ristulop@gmail.com')->exists())->toBeTrue();
    });

    test('Failed Register (missing fields)', function () {
        // Given I am on the "/register" page
        $this->get(route('register'))->assertOk();

        // And I leave one or all required fields blank
        $payload = registerInput([
            'name' => '',
            'email' => '',
            'password' => '',
        ]);

        // When I press the "Register" button
        $response = $this->from(route('register'))->post(route('register'), $payload);

        // Then I should see the error message "Field wajib diisi"
        $response->assertSessionHasErrors([
            'name' => 'Field wajib diisi.',
            'email' => 'Field wajib diisi.',
            'password' => 'Field wajib diisi.',
        ]);

        // And I should remain on the "/register" page
        $response->assertRedirect(route('register'));
    });

    test('Failed Register (email already registered)', function () {
        // Given I am on the "/register" page
        $existing = User::factory()->create(['email' => 'ristulop@gmail.com']);
        $this->get(route('register'))->assertOk();

        // And I fill in "Email" with an already registered email "ristulop@gmail.com"
        $payload = registerInput(['email' => $existing->email]);

        // When I press the "Register" button
        $response = $this->from(route('register'))->post(route('register'), $payload);

        // Then I should see the error message "Email sudah terdaftar"
        $response->assertSessionHasErrors(['email' => 'Email sudah terdaftar']);

        // And I should remain on the "/register" page
        $response->assertRedirect(route('register'));

        // And no second account is created
        expect(User::query()->where('email', $existing->email)->count())->toBe(1);
    });

    test('Failed Register (invalid format or weak password)', function () {
        // Given I am on the "/register" page
        $this->get(route('register'))->assertOk();

        // And I fill in an invalid email format or a password that does not meet criteria
        $payload = registerInput([
            'email' => 'bukan-email',
            'password' => 'pendek',
        ]);

        // When I press the "Register" button
        $response = $this->from(route('register'))->post(route('register'), $payload);

        // Then I should see an error message corresponding to the invalid field
        $response->assertSessionHasErrors([
            'email' => 'Format email tidak valid.',
            'password' => 'Password minimal 8 karakter.',
        ]);

        // And I should remain on the "/register" page
        $response->assertRedirect(route('register'));
    });

    test('Failed Register (database unavailable)', function () {
        // Given the database cannot be reached
        User::creating(function (): void {
            throw new QueryException('sqlite', 'insert into users', [], new RuntimeException('database unavailable'));
        });

        try {
            // When I press the "Register" button with valid data
            $response = $this->from(route('register'))->post(route('register'), registerInput());

            // Then I should see the error message "Pendaftaran tidak dapat diproses saat ini"
            $response->assertSessionHasErrors(['email' => 'Pendaftaran tidak dapat diproses saat ini']);
        } finally {
            User::flushEventListeners();
        }
    });
});
