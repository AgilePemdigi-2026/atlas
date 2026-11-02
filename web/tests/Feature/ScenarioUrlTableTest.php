<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function draftedTestCase(User $user, array $attributes = []): TestCase
{
    $collection = Collection::factory()->for($user)->create();
    $testCase = $collection->testCases()->create(array_merge([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
    ], $attributes));
    $testCase->scenarios()->create(['name' => 'Login berhasil']);
    $testCase->scenarios()->create(['name' => 'Login gagal']);

    return $testCase;
}

test('guests cannot open the scenario url table', function () {
    $testCase = draftedTestCase(User::factory()->create());

    $this->get(route('test-cases.scenario-urls', $testCase))->assertRedirect(route('login'));
});

test('the owner sees one row per scenario with empty inputs', function () {
    $user = User::factory()->create();
    $testCase = draftedTestCase($user);

    $this->actingAs($user)
        ->get(route('test-cases.scenario-urls', $testCase))
        ->assertOk()
        ->assertSee('Login berhasil')
        ->assertSee('Login gagal')
        ->assertSee('Lanjut &amp; Generate', false);
});

test('base url pre-fills scenario rows', function () {
    $user = User::factory()->create();
    $testCase = draftedTestCase($user, ['base_url' => 'https://app.example.com/login/']);

    $this->actingAs($user)
        ->get(route('test-cases.scenario-urls', $testCase))
        ->assertOk()
        ->assertSee('value="https://app.example.com/login/"', false);
});

test('the confirmation form targets the generate route', function () {
    $user = User::factory()->create();
    $testCase = draftedTestCase($user);

    $this->actingAs($user)
        ->get(route('test-cases.scenario-urls', $testCase))
        ->assertOk()
        ->assertSee(route('test-cases.scenario-urls.generate', $testCase), false)
        ->assertSee('Sedang menghasilkan test case', false);
});

test('submitted urls are persisted to the scenario rows', function () {
    $user = User::factory()->create();
    $testCase = draftedTestCase($user);

    $payload = ['scenarios' => $testCase->scenarios()->get()->map(fn ($scenario) => [
        'scenario_id' => $scenario->getKey(),
        'scenario_url' => 'https://app.example.com/'.$scenario->getKey(),
    ])->all()];

    $this->actingAs($user)
        ->post(route('test-cases.scenario-urls.generate', $testCase), $payload)
        ->assertRedirect();

    expect($testCase->scenarios()->pluck('scenario_url')->filter()->count())->toBe(2);
});

test('another user cannot open or generate someone else test case', function () {
    $testCase = draftedTestCase(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->get(route('test-cases.scenario-urls', $testCase))
        ->assertForbidden();
});
