<?php

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createManagedTestCase(User $user)
{
    $collection = Collection::factory()->for($user)->create();

    return $collection->testCases()->create([
        'name' => 'Login Test',
        'user_story' => 'Sebagai pengguna, saya ingin login.',
        'acceptance_criteria' => 'Given saya berada di halaman login',
    ]);
}

test('owner can rename a test case', function () {
    $user = User::factory()->create();
    $testCase = createManagedTestCase($user);

    $this->actingAs($user)
        ->patch(route('test-cases.update', $testCase), [
            'name' => 'Login Berhasil',
        ])
        ->assertRedirect(route('console', [
            'test_case' => $testCase->getKey(),
        ]));

    expect($testCase->fresh()->name)->toBe('Login Berhasil');
});

test('rename requires a valid name', function () {
    $user = User::factory()->create();
    $testCase = createManagedTestCase($user);

    $this->actingAs($user)
        ->patch(route('test-cases.update', $testCase), [
            'name' => '',
        ])
        ->assertSessionHasErrors('name');
});

test('another user cannot rename a test case', function () {
    $testCase = createManagedTestCase(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->patch(route('test-cases.update', $testCase), [
            'name' => 'Unauthorized',
        ])
        ->assertForbidden();

    expect($testCase->fresh()->name)->toBe('Login Test');
});

test('owner can delete a test case', function () {
    $user = User::factory()->create();
    $testCase = createManagedTestCase($user);
    $id = $testCase->getKey();

    $this->actingAs($user)
        ->delete(route('test-cases.destroy', $testCase))
        ->assertRedirect(route('console', [
            'collection' => $testCase->collection_id,
        ]));

    $this->assertDatabaseMissing('test_cases', [
        'test_case_id' => $id,
    ]);
});

test('another user cannot delete a test case', function () {
    $testCase = createManagedTestCase(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->delete(route('test-cases.destroy', $testCase))
        ->assertForbidden();

    $this->assertDatabaseHas('test_cases', [
        'test_case_id' => $testCase->getKey(),
    ]);
});