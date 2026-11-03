<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests see the log in action and no authenticated links', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Log in')
        ->assertDontSee('Log out')
        ->assertDontSee(route('console'), false)
        ->assertDontSee(route('profile'), false);
});

test('authenticated users see console, profile, and log out instead of log in', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Log out')
        ->assertSee(route('console'), false)
        ->assertSee(route('profile'), false)
        ->assertDontSee('Log in');
});

test('logging out returns the header to the guest state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSee('Log out');

    $this->post(route('logout'))->assertRedirect(route('home'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Log in')
        ->assertDontSee('Log out');
});
