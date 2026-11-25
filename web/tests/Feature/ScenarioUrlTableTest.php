<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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

function scenarioUrlPayload(TestCase $testCase, array $urls): array
{
    return ['scenarios' => $testCase->scenarios()->get()->map(fn ($scenario, $index) => [
        'scenario_id' => $scenario->getKey(),
        'scenario_url' => $urls[$index] ?? '',
    ])->all()];
}

describe('F8 Scenario URL Table', function () {
    test('Prompt for Missing Scenario URLs', function () {
        // Given I am on the "Test Case" page
        $user = User::factory()->create();

        // And I fill in the User Story without specifying URLs for each scenario
        $payload = [
            'name' => 'Checkout',
            'user_story' => 'Sebagai pembeli, saya ingin checkout.',
            'acceptance_criteria' => "Scenario: Bayar\nGiven saya di keranjang\n\nScenario: Gagal bayar\nWhen kartu ditolak",
        ];

        $this->actingAs($user)->post(route('test-cases.store'), ['name' => $payload['name']]);

        $testCase = TestCase::query()->sole();

        Http::fake(['*/v1/scenario-urls' => Http::response(['status' => 'error'], 500)]);

        // When I press the "Generate" button
        $response = $this->actingAs($user)
            ->patch(route('test-cases.update', $testCase), $payload);

        // Then the system displays a table for entering scenario URLs
        $response->assertRedirect(route('console', [
            'test_case' => $testCase->getKey(),
            'collection' => $testCase->collection_id,
            'tab' => 'scenario',
        ]));

        $this->actingAs($user)
            ->get(route('console', ['test_case' => $testCase->getKey()]))
            ->assertOk()
            ->assertSee('Bayar')
            ->assertSee('Gagal bayar')
            ->assertSee('name="scenarios[0][scenario_url]"', false)
            ->assertSee('name="scenarios[1][scenario_url]"', false)
            ->assertSee('Continue &amp; Generate', false);
    });

    test('Confirm pre-filled scenario URLs', function () {
        // Given every scenario already has a URL
        $user = User::factory()->create();
        $testCase = draftedTestCase($user, ['base_url' => 'https://app.example.com/login/']);

        // When I press the "Generate" button
        $response = $this->actingAs($user)
            ->get(route('console', ['test_case' => $testCase->getKey()]));

        // Then the table is shown pre-filled for me to confirm
        $response->assertOk()
            ->assertSee('value="https://app.example.com/login/"', false)
            ->assertSee(route('test-cases.scenario-urls.generate', $testCase), false);
    });

    test('A validation error is shown on the console scenario tab', function () {
        // Given I am on the console scenario tab
        $user = User::factory()->create();
        $testCase = draftedTestCase($user);
        Http::preventStrayRequests();

        $consoleUrl = route('console', [
            'test_case' => $testCase->getKey(),
            'tab' => 'scenario',
        ]);

        // When I submit a row with a malformed URL
        $this->actingAs($user)
            ->from($consoleUrl)
            ->followingRedirects()
            ->post(
                route('test-cases.scenario-urls.generate', $testCase),
                scenarioUrlPayload($testCase, ['bukan-url', 'https://app.example.com/login']),
            )
            ->assertOk()
            ->assertSee('Please enter a valid URL.');
    });

    test('Reject an invalid scenario URL', function () {
        // Given the URL table is shown
        $user = User::factory()->create();
        $testCase = draftedTestCase($user);
        Http::preventStrayRequests();

        // When I submit a row with an empty URL
        $response = $this->actingAs($user)->post(
            route('test-cases.scenario-urls.generate', $testCase),
            scenarioUrlPayload($testCase, ['', 'https://app.example.com/login']),
        );

        // Then I should see "This field is required."
        $response->assertSessionHasErrors([
            'scenarios.0.scenario_url' => 'This field is required.',
        ]);

        // When I submit a row with a malformed URL
        $response = $this->actingAs($user)->post(
            route('test-cases.scenario-urls.generate', $testCase),
            scenarioUrlPayload($testCase, ['bukan-url', 'https://app.example.com/login']),
        );

        // Then I should see "Please enter a valid URL."
        $response->assertSessionHasErrors([
            'scenarios.0.scenario_url' => 'Please enter a valid URL.',
        ]);

        // And the service is never called
        Http::assertNothingSent();
    });
});
