<?php

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// F14 - Collection Management
describe('F14 Collection Management', function () {
    test('Create a collection', function () {
        // Given I am logged in
        $user = User::factory()->create();
        // When I create a collection with a name
        $this->actingAs($user)
            ->post(route('collections.store'), ['name' => 'KaryaRasa'])
            ->assertRedirect(route('console'));
        // Then it appears in my sidebar
        $this->actingAs($user)
            ->get(route('console'))
            ->assertOk()
            ->assertSee('KaryaRasa');
        expect($user->collections()->where('name', 'KaryaRasa')->exists())->toBeTrue();
    });

    test('Rename a collection', function () {
        // Given I own a collection
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Lama']);
        // When I change its name
        $this->actingAs($user)
            ->patch(route('collections.update', $collection), ['name' => 'Baru'])
            ->assertRedirect(route('console'));
        // Then the new name is saved
        expect($collection->fresh()->name)->toBe('Baru');
    });

    test('Delete a collection and its test cases', function () {
        // Given I own a collection with test cases
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create();
        $collection->testCases()->create(['name' => 'Login', 'user_story' => 'Sebagai pengguna, saya ingin masuk.']);
        // When I delete the collection
        $this->actingAs($user)
            ->delete(route('collections.destroy', $collection))
            ->assertRedirect(route('console'));
        // Then the collection and its test cases are removed
        expect(Collection::query()->whereKey($collection->getKey())->exists())->toBeFalse();
        $this->assertDatabaseCount('test_cases', 0);
    });

    test("Another user's collection is forbidden", function () {
        // Given a collection belongs to another user
        $owner = User::factory()->create();
        $collection = Collection::factory()->for($owner)->create(['name' => 'Lama']);
        // When I try to update or delete it
        $this->actingAs(User::factory()->create())
            ->patch(route('collections.update', $collection), ['name' => 'Baru'])
            ->assertForbidden();
        $this->actingAs(User::factory()->create())
            ->delete(route('collections.destroy', $collection))
            ->assertForbidden();
        // Then the request is forbidden and nothing changes
        expect($collection->fresh()->name)->toBe('Lama')
            ->and(Collection::query()->whereKey($collection->getKey())->exists())->toBeTrue();
    });

    test('Guest cannot manage collections', function () {
        // Given I am not logged in
        // When I try to create, rename, or delete a collection
        // Then I am redirected to the "/login" page
        $collection = Collection::factory()->create();

        $this->post(route('collections.store'), ['name' => 'Baru'])
            ->assertRedirect(route('login'));
        $this->patch(route('collections.update', $collection), ['name' => 'Baru'])
            ->assertRedirect(route('login'));
        $this->delete(route('collections.destroy', $collection))
            ->assertRedirect(route('login'));
    });

    test('The console lists only the signed in user collections', function () {
        // Given collections belong to different users
        $user = User::factory()->create();
        Collection::factory()->for($user)->create(['name' => 'Toko']);
        Collection::factory()->create(['name' => 'Milik orang lain']);
        // When I open the console
        // Then I only see the collections that are mine
        $this->actingAs($user)
            ->get(route('console'))
            ->assertOk()
            ->assertSee('Toko')
            ->assertDontSee('Milik orang lain');
    });
});
