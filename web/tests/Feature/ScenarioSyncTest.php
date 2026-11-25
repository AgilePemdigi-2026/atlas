<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use App\Services\Generator\ScenarioSync;
use App\Services\Generator\ScenarioUrlClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function okScenarioUrls(array $scenarios): array
{
    return [
        'status' => 'ok',
        'data' => ['scenarios' => $scenarios],
        'meta' => ['model' => 'gpt-6-luna', 'prompt_version' => 'v1', 'warnings' => []],
    ];
}

function syncTestCase(): TestCase
{
    $user = User::factory()->create();
    $testCase = Collection::factory()->for($user)->create()->testCases()->create([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'acceptance_criteria' => 'Skenario: Lama',
    ]);
    $testCase->scenarios()->create([
        'name' => 'Lama',
        'scenario_url' => 'https://app.example.com/lama',
    ]);

    return $testCase;
}

test('apply replaces rows in returned order and stores the fingerprint', function () {
    Http::fake(['*/v1/scenario-urls' => Http::response(okScenarioUrls([
        ['name' => 'Login', 'scenario_url' => 'https://app.example.com/login'],
        ['name' => 'Checkout', 'scenario_url' => null],
    ]), 200)]);
    $testCase = syncTestCase();

    $result = (new ScenarioSync(new ScenarioUrlClient('http://service.test', 'secret', 30)))
        ->apply($testCase, 'Skenario: Login', 'https://app.example.com');

    expect($result)->toBeTrue()
        ->and($testCase->scenarios()->pluck('name')->all())->toBe(['Login', 'Checkout'])
        ->and($testCase->fresh()->scenarios_synced_hash)
        ->toBe(ScenarioSync::fingerprint('Skenario: Login', 'https://app.example.com'));
});

test('apply returns false and leaves rows and fingerprint untouched on failure', function () {
    Http::fake(['*/v1/scenario-urls' => Http::response([], 502)]);
    $testCase = syncTestCase();
    $before = $testCase->scenarios()->pluck('name')->all();

    $result = (new ScenarioSync(new ScenarioUrlClient('http://service.test', 'secret', 30)))
        ->apply($testCase, 'Skenario: Login', 'https://app.example.com');

    expect($result)->toBeFalse()
        ->and($testCase->scenarios()->pluck('name')->all())->toBe($before)
        ->and($testCase->fresh()->scenarios_synced_hash)->toBeNull();
});

test('fingerprint trims and distinguishes base urls', function () {
    expect(ScenarioSync::fingerprint("  Skenario: Login\n", null))
        ->toBe(ScenarioSync::fingerprint('Skenario: Login', null))
        ->not->toBe(ScenarioSync::fingerprint('Skenario: Login', 'https://app.example.com'));
});
