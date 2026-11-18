<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('F20 Documentation Page', function () {
    test('Documentation page renders', function () {
        // Given I am a visitor
        // When I open the documentation page
        $response = $this->get(route('docs'));

        // Then I should see the category sidebar and the topic content
        $response->assertOk()
            ->assertSee('Getting Started')
            ->assertSee('What is ATLAS?')
            ->assertSee('ON THIS PAGE');
    });
});
