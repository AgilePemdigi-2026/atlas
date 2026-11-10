<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function generationSetup(User $user, array $attributes = []): TestCase
{
    $collection = Collection::factory()->for($user)->create();
    $testCase = $collection->testCases()->create(array_merge([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'acceptance_criteria' => "Skenario: Login berhasil\nGiven saya di halaman login\nThen saya melihat dasbor",
    ], $attributes));
    $testCase->scenarios()->create(['name' => 'Login berhasil']);
    $testCase->scenarios()->create(['name' => 'Login gagal']);

    return $testCase;
}

function generatePayload(TestCase $testCase, ?array $urls = null): array
{
    $urls ??= ['https://app.example.com/login', 'https://app.example.com/login?fail=1'];

    return ['scenarios' => $testCase->scenarios()->get()->map(fn ($scenario, $index) => [
        'scenario_id' => $scenario->getKey(),
        'scenario_url' => $urls[$index],
    ])->all()];
}

function okResponse(): array
{
    return [
        'status' => 'ok',
        'data' => [
            'scenarios' => [['scenario' => 'Login berhasil', 'steps' => [['stage' => 'Given', 'action' => 'navigate', 'value' => 'https://app.example.com/login']]]],
            'script' => "from selenium import webdriver\n\ndef login_berhasil(driver, urls):\n    driver.get(urls[0])\n",
        ],
        'meta' => ['attempts' => 2, 'dom_source' => 'live', 'warnings' => [], 'model' => 'gpt-6-luna', 'prompt_version' => 'v1'],
    ];
}

function fakeGenerateService(array $body, int $status = 200): void
{
    Http::preventStrayRequests();
    Http::fake(['*/v1/generate' => Http::response($body, $status)]);
}

test('a successful generate persists output and script and completes the test case', function () {
    $user = User::factory()->create();
    $testCase = generationSetup($user);
    fakeGenerateService(okResponse());

    $response = $this->actingAs($user)
        ->post(route('test-cases.scenario-urls.generate', $testCase), generatePayload($testCase));

    $response->assertRedirect(route('test-cases.show', $testCase));
    $testCase->refresh();

    expect($testCase->status->value)->toBe('completed')
        ->and($testCase->llmOutput->validation_status->value)->toBe('valid')
        ->and($testCase->llmOutput->retry_count)->toBe(2)
        ->and($testCase->llmOutput->generatedScript->status->value)->toBe('completed')
        ->and($testCase->llmOutput->generatedScript->content)->toContain('from selenium import webdriver')
        ->and($testCase->llmOutput->output)->not->toHaveKey('script');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['acceptance_criteria'] === $testCase->acceptance_criteria
        && $request['options']['max_retries'] === 3
        && $request['auth_profile'] === null);
});

test('a service failure maps to copy and marks failed', function () {
    $user = User::factory()->create();
    $testCase = generationSetup($user);
    fakeGenerateService([
        'status' => 'error',
        'data' => null,
        'meta' => ['attempts' => 1],
        'error' => ['code' => 'llm_unavailable', 'message' => 'provider down'],
    ], 502);

    $response = $this->actingAs($user)
        ->post(route('test-cases.scenario-urls.generate', $testCase), generatePayload($testCase));

    $response->assertRedirect(route('test-cases.scenario-urls', $testCase));
    $response->assertSessionHasErrors(['generate' => 'Proses generate gagal, layanan tidak tersedia.']);
    expect($testCase->fresh()->status->value)->toBe('failed')
        ->and($testCase->fresh()->llmOutput->validation_status->value)->toBe('invalid');
});

test('a malformed url is rejected before the service is called', function () {
    $user = User::factory()->create();
    $testCase = generationSetup($user);
    Http::preventStrayRequests();

    $this->actingAs($user)
        ->post(route('test-cases.scenario-urls.generate', $testCase), generatePayload($testCase, ['bukan-url', 'https://app.example.com/login']))
        ->assertSessionHasErrors('scenarios.0.scenario_url');

    Http::assertNothingSent();
});

test('missing scenario url blocks the service call', function () {
    $user = User::factory()->create();
    $testCase = generationSetup($user);
    Http::preventStrayRequests();

    $this->actingAs($user)
        ->post(route('test-cases.scenario-urls.generate', $testCase), generatePayload($testCase, ['', 'https://app.example.com/login']))
        ->assertSessionHasErrors('scenarios.0.scenario_url');

    Http::assertNothingSent();
});

test('another user cannot generate on someone else test case', function () {
    $testCase = generationSetup(User::factory()->create());
    Http::preventStrayRequests();

    $this->actingAs(User::factory()->create())
        ->post(route('test-cases.scenario-urls.generate', $testCase), generatePayload($testCase))
        ->assertForbidden();

    Http::assertNothingSent();
});

test('the draft stores the acceptance criteria for the generate step', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('test-cases.store'), [
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'acceptance_criteria' => "Skenario: Login berhasil\nGiven saya di halaman login",
    ]);

    expect(TestCase::query()->sole()->acceptance_criteria)
        ->toBe("Skenario: Login berhasil\nGiven saya di halaman login");
});

test('a failed re-generate marks the previous script failed instead of leaving it current', function () {
    $user = User::factory()->create();
    $testCase = generationSetup($user);

    Http::preventStrayRequests();
    Http::fakeSequence('*/v1/generate')
        ->push(okResponse())
        ->push([
            'status' => 'error',
            'data' => null,
            'meta' => ['attempts' => 1],
            'error' => ['code' => 'llm_unavailable', 'message' => 'provider down'],
        ], 502);

    $this->actingAs($user)->post(route('test-cases.scenario-urls.generate', $testCase), generatePayload($testCase));

    expect($testCase->fresh()->llmOutput->generatedScript->status->value)->toBe('completed');

    $this->actingAs($user)->post(route('test-cases.scenario-urls.generate', $testCase), generatePayload($testCase));

    $testCase->refresh();

    expect($testCase->status->value)->toBe('failed')
        ->and($testCase->llmOutput->generatedScript->status->value)->toBe('failed');
});
