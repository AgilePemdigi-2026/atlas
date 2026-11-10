<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reviewTestCase(User $user): TestCase
{
    $testCase = Collection::factory()->for($user)->create()->testCases()->create([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'status' => 'completed',
    ]);
    $testCase->scenarios()->create(['name' => 'Login berhasil', 'scenario_url' => 'https://app.example.com/login']);
    $testCase->scenarios()->create(['name' => 'Login gagal']);
    $output = $testCase->llmOutput()->create([
        'output' => [
            'scenarios' => [],
            'meta' => ['attempts' => 2, 'dom_source' => 'live', 'model' => 'gpt-6-luna'],
        ],
        'validation_status' => 'valid',
        'retry_count' => 2,
    ]);
    $output->generatedScript()->create([
        'content' => "from selenium import webdriver\n\ndef login_berhasil(driver, urls):\n    driver.get(urls[0])\n",
        'status' => 'completed',
    ]);

    return $testCase;
}

// F15 - Review Test Case Script
describe('F15 Review Test Case Script', function () {
    test('Successfully Review Test Case Script', function () {
        // Given I am on the "Test Case" page
        // And at least one generated test case exists
        $user = User::factory()->create();
        $testCase = reviewTestCase($user);
        // When I select a test case to review
        // Then I should see the details of the generated Selenium script
        // And I should see the scenario URLs
        $this->actingAs($user)
            ->get(route('test-cases.show', $testCase))
            ->assertOk()
            ->assertSee('Login saya')
            ->assertSee('https://app.example.com/login')
            ->assertSee('Belum ada URL')
            ->assertSee('from selenium import webdriver')
            ->assertSee('Scenario URL')
            ->assertSee('Generated Script');
    });

    test("Another user's test case is forbidden", function () {
        // Given a test case belongs to another user
        $testCase = reviewTestCase(User::factory()->create());
        // When I try to open it
        // Then the request is forbidden
        $this->actingAs(User::factory()->create())
            ->get(route('test-cases.show', $testCase))
            ->assertForbidden();
    });

    test('Guest cannot review', function () {
        // Given I am not logged in
        $testCase = reviewTestCase(User::factory()->create());
        // When I open a test case
        // Then I am redirected to the "/login" page
        $this->get(route('test-cases.show', $testCase))
            ->assertRedirect(route('login'));
    });

    test('The script stays escaped in the view', function () {
        // Given a generated script contains markup
        $user = User::factory()->create();
        $testCase = reviewTestCase($user);
        $testCase->generatedScript->update([
            'content' => "print('<script>alert(1)</script>')\n",
        ]);

        // When I open the test case
        $response = $this->actingAs($user)->get(route('test-cases.show', $testCase));

        // Then the markup is shown as text and not rendered
        $response->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    });
});
