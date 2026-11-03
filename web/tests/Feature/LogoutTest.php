<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('F3 Logout', function () {
    test('Logout ends the session', function () {
        // Given I am logged in
        $user = User::factory()->create();
        $this->actingAs($user);

        // And I am on the Profile page
        $this->get(route('profile'))->assertOk()->assertSee('Log Out');

        // When I press the "Log Out" button
        $response = $this->post(route('logout'));

        // Then my session should end
        $this->assertGuest();

        // And I should be redirected to a guest page
        $response->assertRedirect(route('home'));
        $this->get(route('home'))->assertOk()->assertSee('Log in')->assertDontSee('Log out');
    });

    test('Authenticated users see console, profile, and log out instead of log in', function () {
        // Given I am logged in
        $user = User::factory()->create();

        // When I open the landing page
        $response = $this->actingAs($user)->get(route('home'));

        // Then I see the authenticated navigation
        $response->assertOk()
            ->assertSee('Log out')
            ->assertSee(route('console'), false)
            ->assertSee(route('profile'), false)
            ->assertDontSee('Log in');
    });
});
