<?php

use App\Services\Generator\ErrorCopy;
use App\Services\Generator\GenerateRequest;
use App\Services\Generator\GeneratorClient;
use Illuminate\Support\Facades\Http;

function sampleGenerateRequest(): GenerateRequest
{
    return new GenerateRequest(
        testCaseId: 7,
        userStory: 'Sebagai pengguna, saya ingin masuk.',
        acceptanceCriteria: 'Skenario: Login berhasil',
        baseUrl: 'https://app.example.com',
        scenarios: [['name' => 'Login berhasil', 'scenario_url' => 'https://app.example.com/login']],
    );
}

function sampleGeneratorClient(): GeneratorClient
{
    return new GeneratorClient('http://service.test', 'secret', 180);
}

test('a successful response yields the script and no copy', function () {
    Http::preventStrayRequests();
    Http::fake(['service.test/v1/generate' => Http::response([
        'status' => 'ok',
        'data' => [
            'scenarios' => [['scenario' => 'Login berhasil', 'steps' => []]],
            'script' => "from selenium import webdriver\n",
        ],
        'meta' => ['attempts' => 2, 'dom_source' => 'live', 'warnings' => [], 'model' => 'm', 'prompt_version' => 'v1'],
    ], 200)]);

    $result = sampleGeneratorClient()->send(sampleGenerateRequest());

    expect($result->ok)->toBeTrue()
        ->and($result->payload['data']['script'])->toContain('from selenium import webdriver')
        ->and($result->copy)->toBe('');

    Http::assertSent(fn ($request) => $request->hasHeader('X-Service-Key', 'secret')
        && $request['user_story'] === 'Sebagai pengguna, saya ingin masuk.'
        && ! array_key_exists('auth_profile', $request->data()));
});

test('a service error envelope maps to indonesian copy', function () {
    Http::preventStrayRequests();
    Http::fake(['service.test/v1/generate' => Http::response([
        'status' => 'error',
        'data' => null,
        'meta' => ['attempts' => 1],
        'error' => ['code' => 'llm_unavailable', 'message' => 'provider down'],
    ], 502)]);

    $result = sampleGeneratorClient()->send(sampleGenerateRequest());

    expect($result->ok)->toBeFalse()
        ->and($result->copy)->toBe(ErrorCopy::for('llm_unavailable', 502));
});

test('an auth-layer body does not leak detail and maps to service copy', function () {
    Http::preventStrayRequests();
    Http::fake(['service.test/v1/generate' => Http::response(['detail' => 'Invalid service key.'], 401)]);

    $result = sampleGeneratorClient()->send(sampleGenerateRequest());

    expect($result->copy)->toBe('Proses generate gagal, layanan tidak tersedia.');
});

test('a connection failure maps to service copy', function () {
    Http::preventStrayRequests();
    Http::fake(['service.test/v1/generate' => Http::failedConnection()]);

    $result = sampleGeneratorClient()->send(sampleGenerateRequest());

    expect($result->ok)->toBeFalse()
        ->and($result->copy)->toBe('Proses generate gagal, layanan tidak tersedia.');
});
