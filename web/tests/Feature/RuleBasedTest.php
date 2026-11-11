<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function generationCase(User $user, array $attributes = []): TestCase
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

function generationPayload(TestCase $testCase, ?array $urls = null): array
{
    $urls ??= ['https://app.example.com/login', 'https://app.example.com/login?fail=1'];

    return ['scenarios' => $testCase->scenarios()->get()->map(fn ($scenario, $index) => [
        'scenario_id' => $scenario->getKey(),
        'scenario_url' => $urls[$index],
    ])->all()];
}

/**
 * @param  array<int, array{scenario: string, steps: array<int, array<string, string>>}>  $scenarios
 */
function generatorOkBody(array $scenarios, string $script): array
{
    return [
        'status' => 'ok',
        'data' => [
            'scenarios' => $scenarios,
            'script' => $script,
        ],
        'meta' => ['attempts' => 2, 'dom_source' => 'live', 'warnings' => [], 'model' => 'gpt-6-luna', 'prompt_version' => 'v1'],
    ];
}

function fakeGenerator(array $body, int $status = 200): void
{
    Http::preventStrayRequests();
    Http::fake(['*/v1/generate' => Http::response($body, $status)]);
}

describe('F12 Rule-based Script Generator', function () {
    test('Successful Script Generation', function () {
        // Given I am on the "Test Case" page
        $user = User::factory()->create();
        $testCase = generationCase($user);

        // And I fill in the User Story / Acceptance Criteria and optional Base URL
        // When I press the "Generate" button
        fakeGenerator(generatorOkBody(
            [['scenario' => 'Login berhasil', 'steps' => [['stage' => 'Given', 'action' => 'navigate', 'value' => 'https://app.example.com/login']]]],
            "from selenium import webdriver\n\ndef login_berhasil(driver, urls):\n    driver.get(urls[0])\n",
        ));

        $response = $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), generationPayload($testCase));

        // Then I should see a confirmation that the script was successfully generated
        // And I should still be on the "Test Case" page
        $response->assertRedirect(route('test-cases.show', $testCase));

        // And the test case status is "completed"
        // And the script is saved
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

    test('[llm] Plain story becomes structured steps', function () {
        // Given a plain-language story with Gherkin criteria
        $user = User::factory()->create();
        $story = 'Sebagai pengguna, saya ingin masuk.';
        $criteria = 'Given saya di halaman login When saya menekan masuk Then saya melihat dasbor';
        $steps = [
            ['stage' => 'Given', 'action' => 'navigate', 'value' => 'https://app.example.com/login'],
            ['stage' => 'When', 'action' => 'click', 'target' => 'Masuk'],
            ['stage' => 'Then', 'action' => 'assert_text', 'value' => 'Dasbor'],
        ];

        // When the LLM transport returns a valid extraction
        fakeGenerator(generatorOkBody(
            [['scenario' => 'Login berhasil', 'steps' => $steps]],
            "from selenium import webdriver\n",
        ));

        $testCase = generationCase($user, [
            'user_story' => $story,
            'acceptance_criteria' => $criteria,
        ]);

        $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), generationPayload($testCase));

        // Then the result has scenarios whose steps follow Given, When, Then
        $output = $testCase->fresh()->llmOutput->output;

        expect($output['data']['scenarios'][0]['steps'])->toBe($steps);

        // And no special markup was required in the input
        Http::assertSent(fn ($request) => $request['user_story'] === $story
            && $request['acceptance_criteria'] === $criteria);
    });

    test('Each action and locator renders', function () {
        // Given a validated extraction using each action and locator type
        $user = User::factory()->create();
        $testCase = generationCase($user);
        $script = "from selenium import webdriver\n\n"
            ."def checkout(driver, urls):\n"
            ."    driver.get(urls[0])\n"
            ."    driver.find_element('css selector', '#cart').click()\n"
            ."    driver.find_element('xpath', '//button').click()\n"
            ."    driver.find_element('link text', 'Bayar').click()\n";

        // When the script is generated
        fakeGenerator(generatorOkBody(
            [['scenario' => 'Checkout', 'steps' => [['stage' => 'Given', 'action' => 'navigate', 'value' => 'https://app.example.com']]]],
            $script,
        ));

        $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), generationPayload($testCase));

        // Then the script is saved and rendered
        expect($testCase->fresh()->llmOutput->generatedScript->content)->toBe($script);

        $this->actingAs($user)
            ->get(route('test-cases.show', $testCase))
            ->assertOk()
            ->assertSee($script);
    });
});
