<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use App\Services\Generator\ScenarioSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeScenarioUrls(array $scenarios, int $status = 200): void
{
    Http::preventStrayRequests();
    Http::fake(['*/v1/scenario-urls' => Http::response([
        'status' => $status === 200 ? 'ok' : 'error',
        'data' => $status === 200 ? ['scenarios' => $scenarios] : null,
        'meta' => ['model' => 'gpt-6-luna', 'prompt_version' => 'v1', 'warnings' => []],
        'error' => $status === 200 ? null : ['code' => 'llm_unavailable', 'message' => 'down'],
    ], $status)]);
}

function storyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'acceptance_criteria' => "Skenario: Login berhasil\nGiven saya di halaman login",
        'base_url' => 'https://app.example.com',
    ], $overrides);
}

function flowTestCase(User $user): TestCase
{
    $collection = Collection::factory()->for($user)->create();
    $testCase = $collection->testCases()->create([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'acceptance_criteria' => 'Skenario: Lama',
        'base_url' => 'https://app.example.com',
    ]);
    $testCase->scenarios()->create([
        'name' => 'Lama',
        'scenario_url' => 'https://app.example.com/lama',
    ]);

    return $testCase;
}

test('first story save syncs scenario rows from the service', function () {
    $user = User::factory()->create();
    fakeScenarioUrls([
        ['name' => 'Login berhasil', 'scenario_url' => 'https://app.example.com/login'],
        ['name' => 'Checkout', 'scenario_url' => 'https://app.example.com/checkout'],
    ]);

    $this->actingAs($user)->post(route('test-cases.store'), ['name' => 'Login saya']);
    $testCase = TestCase::query()->sole();

    $response = $this->actingAs($user)->patch(route('test-cases.update', $testCase), storyPayload());

    $response->assertRedirect(route('console', [
        'test_case' => $testCase->getKey(),
        'collection' => $testCase->collection_id,
        'tab' => 'scenario',
    ]));

    expect($testCase->scenarios()->pluck('name')->all())->toBe(['Login berhasil', 'Checkout'])
        ->and($testCase->fresh()->scenarios_synced_hash)
        ->toBe(ScenarioSync::fingerprint(
            storyPayload()['acceptance_criteria'],
            'https://app.example.com',
        ));

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $testCase->getKey()]))
        ->assertOk()
        ->assertSee('Login berhasil')
        ->assertSee('value="https://app.example.com/login"', false);
});

test('an unchanged second save makes no service call', function () {
    $user = User::factory()->create();
    fakeScenarioUrls([
        ['name' => 'Login berhasil', 'scenario_url' => 'https://app.example.com/login'],
    ]);

    $this->actingAs($user)->post(route('test-cases.store'), ['name' => 'Login saya']);
    $testCase = TestCase::query()->sole();

    $this->actingAs($user)->patch(route('test-cases.update', $testCase), storyPayload());
    Http::assertSentCount(1);

    $this->actingAs($user)->patch(route('test-cases.update', $testCase), storyPayload());

    Http::assertSentCount(1);
    expect($testCase->fresh()->scenarios()->pluck('name')->all())->toBe(['Login berhasil']);
});

test('a rename-only update makes no service call', function () {
    $user = User::factory()->create();
    $testCase = flowTestCase($user);
    Http::preventStrayRequests();

    $this->actingAs($user)
        ->patch(route('test-cases.update', $testCase), ['name' => 'Login baru']);

    expect($testCase->fresh()->name)->toBe('Login baru');
    Http::assertNothingSent();
});

test('a changed criteria with a failed sync keeps rows and flashes the failure copy', function () {
    $user = User::factory()->create();
    $testCase = flowTestCase($user);
    $before = $testCase->scenarios()->pluck('name')->all();
    fakeScenarioUrls([], 502);

    $response = $this->actingAs($user)->patch(route('test-cases.update', $testCase), storyPayload([
        'acceptance_criteria' => "Skenario: Baru\nThen selesai",
    ]));

    $response->assertSessionHasErrors([
        'sync' => 'Sinkronisasi gagal, daftar skenario tidak diubah.',
    ]);

    expect($testCase->fresh()->scenarios()->pluck('name')->all())->toBe($before)
        ->and($testCase->fresh()->scenarios_synced_hash)->toBeNull()
        ->and($testCase->fresh()->acceptance_criteria)->toBe("Skenario: Baru\nThen selesai");
});

test('a failed first sync falls back to scenario headings', function () {
    $user = User::factory()->create();
    fakeScenarioUrls([], 502);

    $this->actingAs($user)->post(route('test-cases.store'), ['name' => 'Login saya']);
    $testCase = TestCase::query()->sole();

    $this->actingAs($user)->patch(route('test-cases.update', $testCase), storyPayload([
        'acceptance_criteria' => "Skenario: Login berhasil\nGiven saya di halaman login",
    ]));

    expect($testCase->scenarios()->pluck('name')->all())->toBe(['Login berhasil'])
        ->and($testCase->fresh()->scenarios_synced_hash)->toBeNull();
});

test('a failed sync retries when the same criteria are saved again', function () {
    $user = User::factory()->create();
    $testCase = flowTestCase($user);
    $payload = storyPayload([
        'acceptance_criteria' => "Skenario: Baru\nThen selesai",
    ]);

    Http::preventStrayRequests();
    Http::fakeSequence('*/v1/scenario-urls')
        ->push([], 502)
        ->push([
            'status' => 'ok',
            'data' => ['scenarios' => [
                ['name' => 'Baru', 'scenario_url' => 'https://app.example.com/baru'],
            ]],
            'meta' => ['model' => 'gpt-6-luna', 'prompt_version' => 'v1', 'warnings' => []],
        ], 200);

    $this->actingAs($user)->patch(route('test-cases.update', $testCase), $payload);
    Http::assertSentCount(1);

    $this->actingAs($user)->patch(route('test-cases.update', $testCase), $payload);

    Http::assertSentCount(2);
    expect($testCase->fresh()->scenarios()->pluck('name')->all())->toBe(['Baru'])
        ->and($testCase->fresh()->scenarios_synced_hash)->not->toBeNull();
});

test('the story button reads Sinkronkan when rows exist and Lanjutkan when none', function () {
    $user = User::factory()->create();
    $withRows = flowTestCase($user);

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $withRows->getKey()]))
        ->assertOk()
        ->assertSee('Sinkronkan');

    $collection = Collection::factory()->for($user)->create();
    $empty = $collection->testCases()->create(['name' => 'Kosong']);

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $empty->getKey()]))
        ->assertOk()
        ->assertSee('Lanjutkan');
});

test('a story save without a base URL still replaces names with null URLs', function () {
    $user = User::factory()->create();
    fakeScenarioUrls([
        ['name' => 'Login berhasil', 'scenario_url' => null],
    ]);

    $this->actingAs($user)->post(route('test-cases.store'), ['name' => 'Login saya']);
    $testCase = TestCase::query()->sole();

    $this->actingAs($user)->patch(route('test-cases.update', $testCase), storyPayload([
        'base_url' => null,
        'acceptance_criteria' => 'Skenario: Login berhasil',
    ]));

    expect($testCase->scenarios()->pluck('scenario_url')->all())->toBe([null])
        ->and($testCase->fresh()->scenarios_synced_hash)->not->toBeNull();
});
