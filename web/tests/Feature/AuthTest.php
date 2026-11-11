<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function registerPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Pengguna Baru',
        'email' => 'baru@example.com',
        'password' => 'password123',
        'terms' => '1',
    ], $overrides);
}

test('guests can open the login and register pages', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
});

test('registering stores the account and redirects to login', function () {
    $this->post(route('register'), registerPayload())
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Akun anda berhasil terdaftar');

    expect(User::query()->where('email', 'baru@example.com')->exists())->toBeTrue();
});

test('registration reports missing fields in indonesian', function () {
    $this->post(route('register'), registerPayload([
        'name' => '',
        'email' => '',
        'password' => '',
    ]))->assertSessionHasErrors([
        'name' => 'Field wajib diisi.',
        'email' => 'Field wajib diisi.',
        'password' => 'Field wajib diisi.',
    ]);
});

test('registration rejects a duplicate email', function () {
    User::factory()->create(['email' => 'dipakai@example.com']);

    $this->post(route('register'), registerPayload(['email' => 'dipakai@example.com']))
        ->assertSessionHasErrors(['email' => 'Email sudah terdaftar']);
});

test('registration rejects a malformed email and a short password', function () {
    $this->post(route('register'), registerPayload([
        'email' => 'bukan-email',
        'password' => 'pendek',
    ]))->assertSessionHasErrors([
        'email' => 'Format email tidak valid.',
        'password' => 'Password minimal 8 karakter.',
    ]);
});

// test('logging in starts a session and redirects home', function () {
//     $user = User::factory()->create(['email' => 'masuk@example.com']);

//     $this->post(route('login'), [
//         'email' => $user->email,
//         'password' => 'password',
//     ])->assertRedirect(route('home'));

//     $this->assertAuthenticatedAs($user);
// });

test('logging in starts a session and redirects to the console', function () {
    $user = User::factory()->create(['email' => 'masuk@example.com']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('console'));

    $this->assertAuthenticatedAs($user);
});

test('invalid credentials report the frozen indonesian copy', function () {
    User::factory()->create(['email' => 'masuk@example.com']);

    $this->from(route('login'))
        ->post(route('login'), [
            'email' => 'masuk@example.com',
            'password' => 'salah-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

    $this->assertGuest();
});

test('login requires both fields in indonesian', function () {
    $this->post(route('login'), ['email' => '', 'password' => ''])
        ->assertSessionHasErrors([
            'email' => 'Field wajib diisi.',
            'password' => 'Field wajib diisi.',
        ]);
});

test('authenticated users are redirected away from guest-only pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('login'))->assertRedirect();
    $this->actingAs($user)->get(route('register'))->assertRedirect();
});

test('guests are redirected from the console and profile', function () {
    $this->get(route('console'))->assertRedirect(route('login'));
    $this->get(route('profile'))->assertRedirect(route('login'));
});

test('a user can update their profile inside the settings tab', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Nama Baru', 'email' => 'baru@example.com'])
        ->assertRedirect(route('profile'))
        ->assertSessionHas('status', 'Perubahan berhasil disimpan.');

    expect($user->fresh()->name)->toBe('Nama Baru');
});

test('a user cannot take another account email', function () {
    User::factory()->create(['email' => 'dipakai@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Nama Baru', 'email' => 'dipakai@example.com'])
        ->assertSessionHasErrors('email');
});

test('deleting the account signs the user out and removes the row', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect(User::query()->whereKey($user->getKey())->exists())->toBeFalse();
});
