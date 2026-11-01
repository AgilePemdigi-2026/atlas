<?php

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to login when opening the console', function () {
    $this->get(route('console'))->assertRedirect(route('login'));
});

test('the console lists only the collections of the signed in user', function () {
    $user = User::factory()->create();
    Collection::factory()->for($user)->create(['name' => 'Toko']);
    Collection::factory()->create(['name' => 'Milik orang lain']);

    $this->actingAs($user)
        ->get(route('console'))
        ->assertOk()
        ->assertSee('Toko')
        ->assertDontSee('Milik orang lain');
});

test('a user can create a collection', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('collections.store'), ['name' => 'KaryaRasa'])
        ->assertRedirect(route('console'));

    expect($user->collections()->where('name', 'KaryaRasa')->exists())->toBeTrue();
});

test('a collection requires a name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('collections.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('the owner can rename a collection', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->for($user)->create(['name' => 'Lama']);

    $this->actingAs($user)
        ->patch(route('collections.update', $collection), ['name' => 'Baru'])
        ->assertRedirect(route('console'));

    expect($collection->fresh()->name)->toBe('Baru');
});

test('another user cannot rename a collection', function () {
    $collection = Collection::factory()->create(['name' => 'Lama']);

    $this->actingAs(User::factory()->create())
        ->patch(route('collections.update', $collection), ['name' => 'Baru'])
        ->assertForbidden();

    expect($collection->fresh()->name)->toBe('Lama');
});

test('the owner can delete a collection together with its test cases', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->for($user)->create();
    $collection->testCases()->create(['name' => 'Login', 'user_story' => 'Sebagai pengguna, saya ingin masuk.']);

    $this->actingAs($user)
        ->delete(route('collections.destroy', $collection))
        ->assertRedirect(route('console'));

    expect(Collection::query()->whereKey($collection->getKey())->exists())->toBeFalse();
    $this->assertDatabaseCount('test_cases', 0);
});

test('another user cannot delete a collection', function () {
    $collection = Collection::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('collections.destroy', $collection))
        ->assertForbidden();

    expect(Collection::query()->whereKey($collection->getKey())->exists())->toBeTrue();
});
