<?php

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        // Then I should see the message "Belum ada riwayat test case" (check code)
        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Belum ada riwayat test case.');
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
            ->assertSee('Belum ada riwayat test case.');
    });

    test('Guest cannot open test history', function () {
        // Given I am not logged in
        // When I open the Profile
        // Then I am redirected to the "/login" page
        historyOwner();
        $this->get(route('profile'))
            ->assertRedirect(route('login'));
    });
});
