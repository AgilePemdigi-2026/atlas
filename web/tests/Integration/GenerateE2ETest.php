<?php

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * e2e: Laravel -> FastAPI -> Kenari LLM -> rule-based codegen.
 *
 * Both containers must be running with a live LLM key:
 *     docker compose up -d web generator
 *     vendor/bin/pest tests/Integration
 *
 * The scenario URL is fetched by the generator container, so it has to use the
 * compose service name (`http://web:8000`), not the host-published port.
 */
beforeEach(function () {
    $generator = (string) env('GENERATOR_E2E_URL', 'http://127.0.0.1:18001');
    $app = rtrim((string) env('GENERATOR_E2E_APP_URL', 'http://127.0.0.1:8000'), '/');
    $target = (string) env('GENERATOR_E2E_TARGET', 'http://web:8000/register');

    $generatorHealthy = rescue(
        fn (): bool => Http::timeout(3)->get($generator.'/health')->successful(),
        false,
    );

    if (! $generatorHealthy) {
        $this->markTestSkipped(
            "Generator service is not reachable at {$generator}. Start it with: docker compose up -d generator"
        );
    }

    $registerPage = rescue(
        fn () => Http::timeout(3)->get($app.'/register'),
        null,
    );

    if (! $registerPage?->successful()) {
        $this->markTestSkipped(
            "Web app is not reachable at {$app}. Start it with: docker compose up -d web"
        );
    }

    // A 200 with an empty body means the app is up but not rendering (stale image).
    if (! str_contains($registerPage->body(), 'name="email"')) {
        $this->fail(
            "{$app}/register did not render the register form. Rebuild the web image: docker compose up -d --build web"
        );
    }

    config([
        'services.generator.url' => $generator,
        'e2e.target_url' => $target,
    ]);

    if ($key = env('GENERATOR_E2E_KEY')) {
        config(['services.generator.key' => $key]);
    }
});

test('Successful Script Generation against the live service', function () {
    // Given I am on the "Test Case" page
    $user = User::factory()->create();
    $collection = Collection::factory()->for($user)->create();

    $targetUrl = config('e2e.target_url');

    $acceptanceCriteria = <<<GHERKIN
    Scenario: Register Success
    Given I am on the "{$targetUrl}" page
    And I fill in "Full name" with "Rizztul"
    And I fill in "Email address" with "ristulop@gmail.com"
    And I fill in "Password" with "ristulristul"
    When I press the "Create account" button
    Then I should see the message "Akun anda berhasil terdaftar"
    GHERKIN;

    // And I fill in the User Story / Acceptance Criteria and optional Base URL
    $testCase = $collection->testCases()->create([
        'name' => 'Register Success',
        'user_story' => 'Sebagai pengunjung, saya ingin mendaftar akun baru lewat halaman register.',
        'acceptance_criteria' => $acceptanceCriteria,
        'base_url' => $targetUrl,
    ]);
    $scenario = $testCase->scenarios()->create(['name' => 'Register Success']);

    $payload = ['scenarios' => [[
        'scenario_id' => $scenario->getKey(),
        'scenario_url' => $targetUrl,
    ]]];

    // When I press the "Generate" button
    $response = $this->actingAs($user)
        ->post(route('test-cases.scenario-urls.generate', $testCase), $payload);

    // Then I should see a confirmation that the script was successfully generated
    $response->assertRedirect(route('test-cases.show', $testCase));

    $testCase->refresh();
    $output = $testCase->llmOutput?->output;

    // And the test case status is "completed"
    expect($testCase->status->value)->toBe('completed')
        ->and($testCase->llmOutput->validation_status->value)->toBe('valid')
        ->and($output['meta']['dom_source'])->toBe('live')
        ->and($output['meta']['attempts'])->toBeGreaterThanOrEqual(1);

    // And the extraction follows Given, When, Then
    $scenarios = $output['data']['scenarios'];

    expect($scenarios)->not->toBeEmpty();

    foreach ($scenarios as $scenario) {
        expect($scenario['steps'])->not->toBeEmpty()
            ->and($scenario['steps'][0]['stage'])->toBe('Given');
    }

    // And the rule-based generator produced a real Selenium script
    $script = $testCase->llmOutput->generatedScript->content;

    expect($script)->toContain('from selenium import webdriver')
        ->and($script)->toContain($targetUrl);

    assertValidPython($script);

    // And I should see the script on the review page
    $this->actingAs($user)
        ->get(route('test-cases.show', $testCase))
        ->assertOk()
        ->assertSee($script);

    if (env('GENERATOR_E2E_DUMP')) {
        fwrite(STDERR, "\n".$script."\n");
    }
})->group('integration');

function assertValidPython(string $script): void
{
    $python = trim((string) shell_exec('command -v python3'));

    if ($python === '') {
        test()->markTestSkipped('python3 is not installed; skipped the syntax check.');
    }

    $path = tempnam(sys_get_temp_dir(), 'atlas-e2e-');
    file_put_contents($path, $script);

    exec(
        sprintf(
            'python3 -c %s %s 2>&1',
            escapeshellarg('import ast, sys; ast.parse(open(sys.argv[1]).read())'),
            escapeshellarg($path),
        ),
        $compileOutput,
        $exitCode,
    );

    @unlink($path);

    expect($exitCode)->toBe(0, "Generated script is not valid Python:\n".implode("\n", $compileOutput));
}
