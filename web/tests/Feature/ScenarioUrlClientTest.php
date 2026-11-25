<?php

use App\Services\Generator\ScenarioUrlClient;
use Illuminate\Support\Facades\Http;

test('a successful response returns normalised scenario rows', function () {
    Http::preventStrayRequests();
    Http::fake(['service.test/v1/scenario-urls' => Http::response([
        'status' => 'ok',
        'data' => ['scenarios' => [
            ['name' => 'Login', 'scenario_url' => 'https://app.example.com/login'],
            ['name' => 'Checkout', 'scenario_url' => null],
        ]],
        'meta' => ['model' => 'gpt-6-luna', 'prompt_version' => 'v1', 'warnings' => []],
    ], 200)]);

    $rows = (new ScenarioUrlClient('http://service.test', 'secret', 30))
        ->suggest('Login saya', 'Skenario: Login', 'https://app.example.com');

    expect($rows)->toBe([
        ['name' => 'Login', 'scenario_url' => 'https://app.example.com/login'],
        ['name' => 'Checkout', 'scenario_url' => null],
    ]);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->hasHeader('X-Service-Key', 'secret')
        && $request['test_case_name'] === 'Login saya'
        && $request['acceptance_criteria'] === 'Skenario: Login'
        && $request['base_url'] === 'https://app.example.com');
});

test('an error envelope returns null', function () {
    Http::fake(['service.test/v1/scenario-urls' => Http::response([
        'status' => 'error',
        'data' => null,
        'error' => ['code' => 'llm_unavailable', 'message' => 'down'],
    ], 502)]);

    expect((new ScenarioUrlClient('http://service.test', 'secret', 30))
        ->suggest('Login saya', 'Skenario: Login', null))->toBeNull();
});

test('a malformed body returns null', function () {
    Http::fake(['service.test/v1/scenario-urls' => Http::response([
        'status' => 'ok',
        'data' => ['scenarios' => 'nope'],
    ], 200)]);

    expect((new ScenarioUrlClient('http://service.test', 'secret', 30))
        ->suggest('Login saya', 'Skenario: Login', null))->toBeNull();
});

test('an empty scenario list returns null', function () {
    Http::fake(['service.test/v1/scenario-urls' => Http::response([
        'status' => 'ok',
        'data' => ['scenarios' => []],
        'meta' => ['model' => 'gpt-6-luna', 'prompt_version' => 'v1', 'warnings' => []],
    ], 200)]);

    expect((new ScenarioUrlClient('http://service.test', 'secret', 30))
        ->suggest('Login saya', 'Skenario: Login', null))->toBeNull();
});

test('a connection failure returns null', function () {
    Http::fake(['service.test/v1/scenario-urls' => Http::failedConnection()]);

    expect((new ScenarioUrlClient('http://service.test', 'secret', 30))
        ->suggest('Login saya', 'Skenario: Login', null))->toBeNull();
});
