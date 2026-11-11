
<?php

use App\Models\AuthProfile;
use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function authProfilePayload(array $overrides = []): array
{
    return array_merge([
        'login_url' => 'https://example.com/login',
        'credentials' => [
            'email' => 'tester@example.com',
            'password' => 'dummy-test-password',
        ],
    ], $overrides);
}

function makeOwnedTestCase(User $user): TestCase
{
    $collection = Collection::factory()->for($user)->create();

    return $collection->testCases()->create([
        'name' => 'Login Test',
        'user_story' => 'Sebagai pengguna, saya ingin login.',
        'acceptance_criteria' => 'Given saya berada di halaman login',
    ]);
}

test('user can save an auth profile', function () {
    $user = User::factory()->create();
    $testCase = makeOwnedTestCase($user);

    $this->actingAs($user)
        ->putJson(route('auth-profiles.upsert', $testCase->getKey()), authProfilePayload())
        ->assertOk()
        ->assertJsonPath('data.login_url', 'https://example.com/login');

    expect($testCase->authProfile()->exists())->toBeTrue();
});

test('user can update an existing auth profile', function () {
    $user = User::factory()->create();
    $testCase = makeOwnedTestCase($user);

    $this->actingAs($user)
        ->putJson(route('auth-profiles.upsert', $testCase->getKey()), authProfilePayload())
        ->assertOk();

    $this->putJson(
        route('auth-profiles.upsert', $testCase->getKey()),
        authProfilePayload([
            'login_url' => 'https://example.com/sign-in',
        ])
    )->assertOk();

    expect($testCase->authProfile()->count())->toBe(1)
        ->and($testCase->authProfile()->first()->login_url)
        ->toBe('https://example.com/sign-in');
});

test('user can delete an auth profile', function () {
    $user = User::factory()->create();
    $testCase = makeOwnedTestCase($user);

    $this->actingAs($user)
        ->putJson(route('auth-profiles.upsert', $testCase->getKey()), authProfilePayload())
        ->assertOk();

    $this->deleteJson(route('auth-profiles.destroy', $testCase->getKey()))
        ->assertOk();

    expect($testCase->authProfile()->exists())->toBeFalse();
});

test('user cannot modify another users auth profile', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $testCase = makeOwnedTestCase($owner);

    $this->actingAs($otherUser)
        ->putJson(route('auth-profiles.upsert', $testCase->getKey()), authProfilePayload())
        ->assertNotFound();

    $this->deleteJson(route('auth-profiles.destroy', $testCase->getKey()))
        ->assertNotFound();

    expect(AuthProfile::query()->count())->toBe(0);
});

test('auth profile credentials are encrypted in the database', function () {
    $user = User::factory()->create();
    $testCase = makeOwnedTestCase($user);

    $this->actingAs($user)
        ->putJson(route('auth-profiles.upsert', $testCase->getKey()), authProfilePayload())
        ->assertOk();

    $profile = $testCase->authProfile()->firstOrFail();

    $rawCredentials = DB::table('auth_profiles')
        ->where('auth_profile_id', $profile->getKey())
        ->value('credentials');

    expect($rawCredentials)
        ->not->toContain('dummy-test-password')
        ->and($profile->credentials['email'])
        ->toBe('tester@example.com')
        ->and($profile->credentials['password'])
        ->toBe('dummy-test-password')
        ->and($profile->toArray())
        ->not->toHaveKey('credentials');
});