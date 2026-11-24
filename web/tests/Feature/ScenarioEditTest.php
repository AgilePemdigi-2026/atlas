<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function scenarioEditTestCase(User $user): TestCase
{
    $collection = Collection::factory()->for($user)->create();

    $testCase = $collection->testCases()->create([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
    ]);

    $testCase->scenarios()->create([
        'name' => 'Login berhasil',
        'scenario_url' => 'https://app.example.com/login',
    ]);

    return $testCase;
}

test('owner can save a scenario name and URL', function () {
    $user = User::factory()->create();
    $testCase = scenarioEditTestCase($user);
    $scenario = $testCase->scenarios()->first();

    Http::preventStrayRequests();

    $this->actingAs($user)
        ->patchJson(route('scenarios.update', $scenario), [
            'name' => 'Login dengan kredensial valid',
            'scenario_url' => 'https://app.example.com/login?next=/home',
        ])
        ->assertOk()
        ->assertJson(['message' => 'Skenario disimpan.']);

    expect($scenario->fresh()->name)->toBe('Login dengan kredensial valid')
        ->and($scenario->fresh()->scenario_url)->toBe('https://app.example.com/login?next=/home');

    Http::assertNothingSent();
});

test('scenario save requires a name', function () {
    $user = User::factory()->create();
    $scenario = scenarioEditTestCase($user)->scenarios()->first();

    $this->actingAs($user)
        ->patchJson(route('scenarios.update', $scenario), [
            'name' => '',
            'scenario_url' => 'https://app.example.com/login',
        ])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Field wajib diisi.']);
});

test('scenario save rejects a malformed URL', function () {
    $user = User::factory()->create();
    $scenario = scenarioEditTestCase($user)->scenarios()->first();

    $this->actingAs($user)
        ->patchJson(route('scenarios.update', $scenario), [
            'name' => 'Login berhasil',
            'scenario_url' => 'bukan-url',
        ])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Format URL tidak valid.']);
});

test('another user cannot save a scenario', function () {
    $testCase = scenarioEditTestCase(User::factory()->create());
    $scenario = $testCase->scenarios()->first();

    $this->actingAs(User::factory()->create())
        ->patchJson(route('scenarios.update', $scenario), [
            'name' => 'Unauthorized',
            'scenario_url' => 'https://app.example.com/login',
        ])
        ->assertForbidden();

    expect($scenario->fresh()->name)->toBe('Login berhasil');
});

test('guests cannot save a scenario', function () {
    $testCase = scenarioEditTestCase(User::factory()->create());
    $scenario = $testCase->scenarios()->first();

    $this->patch(route('scenarios.update', $scenario), [
        'name' => 'Unauthorized',
        'scenario_url' => 'https://app.example.com/login',
    ])->assertRedirect(route('login'));

    expect($scenario->fresh()->name)->toBe('Login berhasil');
});

test('the console renders editable scenario fields', function () {
    $user = User::factory()->create();
    $testCase = scenarioEditTestCase($user);
    $scenario = $testCase->scenarios()->first();

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $testCase->getKey()]))
        ->assertOk()
        ->assertSee('name="scenarios[0][name]"', false)
        ->assertSee('data-save-url="'.route('scenarios.update', $scenario).'"', false)
        ->assertSee('data-scenario-name', false)
        ->assertSee('data-scenario-url', false);
});

test('generate persists an edited scenario name from the table', function () {
    $user = User::factory()->create();
    $testCase = scenarioEditTestCase($user);
    $scenario = $testCase->scenarios()->first();

    Http::preventStrayRequests();
    Http::fake(['*/v1/generate' => Http::response([
        'status' => 'ok',
        'data' => [
            'scenarios' => [[
                'scenario' => 'Login dengan kredensial valid',
                'steps' => [[
                    'stage' => 'Given',
                    'action' => 'navigate',
                    'value' => 'https://app.example.com/login',
                ]],
            ]],
            'script' => "from selenium import webdriver\n\ndef login_berhasil(driver, urls):\n    driver.get(urls[0])\n",
        ],
        'meta' => ['attempts' => 1, 'dom_source' => 'live', 'warnings' => [], 'model' => 'gpt-6-luna', 'prompt_version' => 'v1'],
    ], 200)]);

    $this->actingAs($user)
        ->post(route('test-cases.scenario-urls.generate', $testCase), [
            'scenarios' => [[
                'scenario_id' => $scenario->getKey(),
                'name' => 'Login dengan kredensial valid',
                'scenario_url' => 'https://app.example.com/login',
            ]],
        ])
        ->assertRedirect(route('console', [
            'test_case' => $testCase->getKey(),
            'collection' => $testCase->collection_id,
            'tab' => 'script',
        ]));

    expect($scenario->fresh()->name)->toBe('Login dengan kredensial valid');
});
