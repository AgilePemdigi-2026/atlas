<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('F5 Landing Page', function () {
    test('Landing page renders', function () {
        // Given I am a visitor

        // When I open the "/" page
        $response = $this->get(route('home'));

        // Then I should see the hero section and the feature sections
        $response->assertOk()
            ->assertSee('Turn User Stories')
            ->assertSee('From Requirements to Automated Testing')
            ->assertSee('Everything You Need for Test Automation')
            ->assertSee('From a Simple Story to a Test Script');
    });

    test('Landing page calls to action', function () {
        // Given I am on the landing page
        $response = $this->get(route('home'));

        // Then the primary button should link to the register page
        $response->assertSee('href="'.route('register').'"', false)
            ->assertSee('Create Your Account');

        // And the secondary button should link to further information
        $response->assertSee('href="#process"', false)
            ->assertSee('Explore Process');
    });
});
