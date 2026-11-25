<?php

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function historyOwner(): User
{
    return User::factory()->create();
}

// F17 - Test History
describe('F17 Test History', function () {
    test('No Test Case History Found', function () {
        // Given I am on the "Test Case" page
        // And no test case history has been created yet
        $user = historyOwner();
        // Then I should see the message "No Test Case history yet."
        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('No Test Case history yet.');
    });

    test('Test history is paginated', function () {
        // Given I have more than 6 test cases
        $user = historyOwner();
        $collection = Collection::factory()->for($user)->create();
        foreach (range(1, 7) as $index) {
            $collection->testCases()->create([
                'name' => 'Login '.$index,
                'user_story' => 'Sebagai pengguna, saya ingin masuk.',
                'status' => 'completed',
            ]);
        }
        // When I open the Profile "Test History" tab
        $panel = $this->actingAs($user)->get(route('profile'))->assertOk();
        // Then I see 6 per page and the seventh case moves to the next page
        foreach (range(2, 7) as $index) {
            $panel->assertSee('Login '.$index);
        }
        $panel->assertDontSee('Login 1');
        // And the empty history copy stays reserved for a user without cases
        $this->actingAs(User::factory()->create())
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('No Test Case history yet.');
    });

    test('Guest cannot open test history', function () {
        // Given I am not logged in
        // When I open the Profile
        // Then I am redirected to the "/login" page
        historyOwner();
        $this->get(route('profile'))
            ->assertRedirect(route('login'));
    });

    test('History cannot be loaded', function () {
        // Given the database cannot be reached
        $user = historyOwner();
        Schema::drop('test_cases');

        // When I open the Profile "Test History" tab
        // Then I should see the message "Unable to load data right now."
        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Unable to load data right now.');
    });

    test('Selecting a row opens the test case detail', function () {
        // Given I have at least one test case in my history
        $user = historyOwner();
        $collection = Collection::factory()->for($user)->create();
        $testCase = $collection->testCases()->create([
            'name' => 'Login terlindungi',
            'user_story' => 'Sebagai pengguna, saya ingin masuk.',
            'status' => 'completed',
        ]);

        // When I open the Profile "Test History" tab
        $panel = $this->actingAs($user)->get(route('profile'))->assertOk();

        // Then the row links to the test case detail
        $panel->assertSee(route('console', ['test_case' => $testCase->getKey()]), false);

        // And the detail page shows that test case
        $this->actingAs($user)
            ->get(route('console', ['test_case' => $testCase->getKey()]))
            ->assertOk()
            ->assertSee('Login terlindungi');
    });
});
