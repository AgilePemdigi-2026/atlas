<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function failedTestCase(User $user, array $attributes = []): TestCase
{
    $collection = Collection::factory()->for($user)->create();
    $testCase = $collection->testCases()->create(array_merge([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'acceptance_criteria' => "Skenario: Login berhasil\nGiven saya di halaman login",
        'base_url' => 'https://app.example.com',
    ], $attributes));
    $testCase->scenarios()->createMany([
        ['name' => 'Login berhasil'],
        ['name' => 'Login gagal'],
    ]);

    return $testCase;
}

function failedGeneratePayload(TestCase $testCase): array
{
    return ['scenarios' => $testCase->scenarios()->get()->map(fn ($scenario, $index) => [
        'scenario_id' => $scenario->getKey(),
        'scenario_url' => 'https://app.example.com/login'.($index === 0 ? '' : '?fail=1'),
    ])->all()];
}

function fakeFailedGenerate(array $body, int $status = 502): void
{
    Http::preventStrayRequests();
    Http::fake(['*/v1/generate' => Http::response($body, $status)]);
}

// F13 - Error Handling for Generate
describe('F13 Error Handling for Generate', function () {
    test('Failed Generate (target URL unreachable)', function () {
        // Given I have filled in the test case generation data
        $user = User::factory()->create();
        $testCase = failedTestCase($user);
        // When I press the "Generate" button
        // And the target web application is down or unreachable
        fakeFailedGenerate([
            'status' => 'error',
            'data' => null,
            'meta' => ['attempts' => 1],
            'error' => ['code' => 'invalid_url', 'message' => 'Target URL is unreachable.'],
        ], 502);

        $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), failedGeneratePayload($testCase))
            // Then I should see the error message "URL tidak dapat diakses"
            ->assertRedirect(route('test-cases.scenario-urls', $testCase))
            ->assertSessionHasErrors(['generate' => 'URL tidak dapat diakses.']);
        // And the test case status is "failed"
        expect($testCase->fresh()->status->value)->toBe('failed');
    });

    test('Failed Generate (LLM service unavailable)', function () {
        // Given I have filled in the test case generation data
        $user = User::factory()->create();
        $testCase = failedTestCase($user);
        // When I press the "Generate" button
        // And the LLM service encounters a timeout or server error
        fakeFailedGenerate([
            'status' => 'error',
            'data' => null,
            'meta' => ['attempts' => 1],
            'error' => ['code' => 'llm_unavailable', 'message' => 'provider down'],
        ], 502);

        $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), failedGeneratePayload($testCase))
            // Then I should see the error message "Proses generate gagal, layanan tidak tersedia"
            ->assertRedirect(route('test-cases.scenario-urls', $testCase))
            ->assertSessionHasErrors(['generate' => 'Proses generate gagal, layanan tidak tersedia.']);
        // And the test case status is "failed"
        expect($testCase->fresh()->status->value)->toBe('failed');
    });

    test('Failed Generate (no matching DOM elements)', function () {
        // Given extraction cannot match any DOM element after the maximum retries
        // When I press the "Generate" button
        $user = User::factory()->create();
        $testCase = failedTestCase($user);
        fakeFailedGenerate([
            'status' => 'error',
            'data' => null,
            'meta' => ['attempts' => 4],
            'error' => ['code' => 'invalid_response', 'message' => 'targets not found'],
        ], 502);

        $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), failedGeneratePayload($testCase))
            // Then I should see the generic generation-failure message
            ->assertRedirect(route('test-cases.scenario-urls', $testCase))
            ->assertSessionHasErrors(['generate' => 'Gagal menghasilkan script. Coba lagi atau periksa isian skenario Anda.']);
        // And the test case status is "failed"
        expect($testCase->fresh()->status->value)->toBe('failed');
    });

    test('Failed Generate (login failed)', function () {
        // Given the target rejects the stored credentials
        $user = User::factory()->create();
        $testCase = failedTestCase($user);
        fakeFailedGenerate([
            'status' => 'error',
            'data' => null,
            'meta' => ['attempts' => 1],
            'error' => ['code' => 'auth_failed', 'message' => 'Login rejected.'],
        ], 502);

        $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), failedGeneratePayload($testCase))
            // Then I should see the error message "Gagal login. Periksa kredensial atau Login URL."
            ->assertRedirect(route('test-cases.scenario-urls', $testCase))
            ->assertSessionHasErrors(['generate' => 'Gagal login. Periksa kredensial atau Login URL.']);
        // And the test case status is "failed"
        expect($testCase->fresh()->status->value)->toBe('failed');
    });

    test('Guest cannot generate a script', function () {
        // Given I am not logged in
        // When I press the "Generate" button
        // Then I am redirected to the "/login" page
        $testCase = failedTestCase(User::factory()->create());
        Http::preventStrayRequests();

        $this->post(route('test-cases.scenario-urls.generate', $testCase), failedGeneratePayload($testCase))
            ->assertRedirect(route('login'));
    });

    test("Another user's test case cannot be generated", function () {
        // Given a test case belongs to another user
        // When I try to generate it
        // Then the request is forbidden and nothing is sent
        $testCase = failedTestCase(User::factory()->create());
        Http::preventStrayRequests();

        $this->actingAs(User::factory()->create())
            ->post(route('test-cases.scenario-urls.generate', $testCase), failedGeneratePayload($testCase))
            ->assertForbidden();
        // And the test case status is unchanged
        expect($testCase->fresh()->status->value)->toBe('pending');
    });

    test('Successful Generate', function () {
        // Given I have filled in the test case generation data
        // When the generator returns a valid script
        // Then the test case status is "completed"
        $user = User::factory()->create();
        $testCase = failedTestCase($user);
        Http::preventStrayRequests();
        Http::fake(['*/v1/generate' => Http::response([
            'status' => 'ok',
            'data' => [
                'scenarios' => [],
                'script' => "from selenium import webdriver\n\ndef login_berhasil(driver, urls):\n    driver.get(urls[0])\n",
            ],
            'meta' => ['attempts' => 1, 'dom_source' => 'snapshot'],
        ], 200)]);

        $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), failedGeneratePayload($testCase))
            ->assertRedirect(route('test-cases.show', $testCase));
        // And the script is saved
        expect($testCase->fresh()->status->value)->toBe('completed');
    });
});
