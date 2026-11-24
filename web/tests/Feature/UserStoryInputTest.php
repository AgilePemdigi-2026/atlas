<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function storyInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'acceptance_criteria' => "Skenario: Login berhasil\nGiven saya di halaman login",
    ], $overrides);
}

describe('F6 Input User Story', function () {
    test('Failed Generate (missing user story)', function () {
        // Given I am on the "Test Case" page
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('test-cases.store'), ['name' => 'Login saya']);

        $testCase = TestCase::query()->sole();
        $console = route('console', ['test_case' => $testCase->getKey()]);
        $this->actingAs($user)->get($console)->assertOk();

        // And I leave the User Story field blank
        $payload = storyInput([
            'user_story' => '',
            'acceptance_criteria' => '',
        ]);

        // When I press the "Generate" button
        $response = $this->from($console)
            ->actingAs($user)
            ->patch(route('test-cases.update', $testCase), $payload);

        // Then I should see the error message "Field wajib diisi"
        $response->assertSessionHasErrors([
            'user_story' => 'Field wajib diisi.',
            'acceptance_criteria' => 'Field wajib diisi.',
        ], null, 'updateTestCase');
        expect($testCase->scenarios()->doesntExist())->toBeTrue();
    });

    test('Story with several scenarios is split', function () {
        // Given I fill in a story whose criteria contain two "Scenario:" headings
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Toko']);

        $payload = storyInput([
            'collection_id' => $collection->getKey(),
            'acceptance_criteria' => "Scenario: Login berhasil\nGiven saya di halaman login\n\nScenario: Login gagal\nWhen password salah",
        ]);

        $this->actingAs($user)->post(route('test-cases.store'), [
            'name' => $payload['name'],
            'collection_id' => $collection->getKey(),
        ]);

        $testCase = TestCase::query()->sole();

        Http::fake(['*/v1/scenario-urls' => Http::response(['status' => 'error'], 500)]);

        // When I press the "Generate" button
        $response = $this->actingAs($user)
            ->patch(route('test-cases.update', $testCase), $payload);

        // Then two scenario rows are created for the test case
        $response->assertRedirect(route('console', [
            'test_case' => $testCase->getKey(),
            'collection' => $testCase->collection_id,
            'tab' => 'scenario',
        ]));
        expect($testCase->scenarios()->pluck('name')->all())
            ->toBe(['Login berhasil', 'Login gagal']);
    });

    test('Story without scenario headings falls back to one scenario', function () {
        // Given I fill in a story with no scenario headings
        $user = User::factory()->create();

        $payload = storyInput([
            'name' => 'Checkout',
            'acceptance_criteria' => 'Given saya di beranda Then saya melihat judul',
        ]);

        $this->actingAs($user)->post(route('test-cases.store'), ['name' => $payload['name']]);

        $testCase = TestCase::query()->sole();

        Http::fake(['*/v1/scenario-urls' => Http::response(['status' => 'error'], 500)]);

        // When I press the "Generate" button
        $this->actingAs($user)
            ->patch(route('test-cases.update', $testCase), $payload);

        // Then one scenario row is created
        expect($testCase->scenarios()->pluck('name')->all())
            ->toBe(['Checkout']);
    });

    test('Guest cannot create a test case', function () {
        // Given I am not logged in

        // When I submit a story
        $response = $this->post(route('test-cases.store'), storyInput());

        // Then I am redirected to the "/login" page
        $response->assertRedirect(route('login'));
        expect(TestCase::query()->count())->toBe(0);
    });

    test('Another user cannot target someone else collection', function () {
        // Given a collection belongs to another user
        $foreign = Collection::factory()->create();

        // When I submit a story into that collection
        $response = $this->actingAs(User::factory()->create())
            ->post(route('test-cases.store'), storyInput(['collection_id' => $foreign->getKey()]));

        // Then the request is forbidden
        $response->assertNotFound();
        expect(TestCase::query()->count())->toBe(0);
    });
});
