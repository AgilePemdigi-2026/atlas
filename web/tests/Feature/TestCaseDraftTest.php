<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function draftPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'acceptance_criteria' => "Skenario: Login berhasil\nGiven saya di halaman login",
    ], $overrides);
}

test('guests cannot create a test case', function () {
    $this->post(route('test-cases.store'), draftPayload())->assertRedirect(route('login'));
});

test('a test case is stored as a pending draft with parsed scenarios', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->for($user)->create(['name' => 'Toko']);

    $this->actingAs($user)
        ->post(route('test-cases.store'), draftPayload(['collection_id' => $collection->getKey()]));

    $testCase = TestCase::query()->sole();

    expect($testCase->status->value)->toBe('pending')
        ->and($testCase->collection_id)->toBe($collection->getKey())
        ->and($testCase->scenarios()->doesntExist())->toBeTrue();

    Http::fake(['*/v1/scenario-urls' => Http::response(['status' => 'error'], 500)]);

    $response = $this->actingAs($user)
        ->patch(route('test-cases.update', $testCase), draftPayload());

    $response->assertRedirect(route('console', [
        'test_case' => $testCase->getKey(),
        'collection' => $testCase->collection_id,
        'tab' => 'scenario',
    ]));
    expect($testCase->scenarios()->pluck('name')->all())->toBe(['Login berhasil']);
});

test('without a collection the draft lands in the root collection', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('test-cases.store'), draftPayload());

    $testCase = TestCase::query()->sole();
    expect($testCase->collection->name)->toBe('Default')
        ->and($user->collections()->where('name', 'Default')->count())->toBe(1);
});

test('criteria without scenario headings yield one scenario', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('test-cases.store'), draftPayload([
        'name' => 'Checkout',
    ]));

    $testCase = TestCase::query()->sole();

    Http::fake(['*/v1/scenario-urls' => Http::response(['status' => 'error'], 500)]);

    $this->actingAs($user)
        ->patch(route('test-cases.update', $testCase), draftPayload([
            'name' => 'Checkout',
            'acceptance_criteria' => 'Given saya di beranda Then saya melihat judul',
        ]));

    expect($testCase->scenarios()->pluck('name')->all())->toBe(['Checkout']);
});

test('the required fields are enforced', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('test-cases.store'), draftPayload());

    $testCase = TestCase::query()->sole();

    $this->actingAs($user)
        ->patch(route('test-cases.update', $testCase), draftPayload([
            'user_story' => '',
            'acceptance_criteria' => '',
        ]))
        ->assertSessionHasErrors([
            'user_story' => 'This field is required.',
            'acceptance_criteria' => 'This field is required.',
        ], null, 'updateTestCase');

    expect($testCase->scenarios()->doesntExist())->toBeTrue();
});

test('the console shows the user story form for a selected draft', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->for($user)->create(['name' => 'Toko']);
    $testCase = $collection->testCases()->create(['name' => 'Login']);

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $testCase->getKey()]))
        ->assertOk()
        ->assertSee(route('test-cases.update', $testCase), false)
        ->assertSee('Continue')
        ->assertSee('Acceptance criteria')
        ->assertDontSee('Isi tab User Story dipasang di tahap 2.');
});

test('the active collection is marked current in the sidebar', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->for($user)->create(['name' => 'Toko']);

    $this->actingAs($user)
        ->get(route('console', ['collection' => $collection->getKey()]))
        ->assertOk()
        ->assertSee('aria-current="page"', false)
        ->assertSee('name="collection_id" value="'.$collection->getKey().'"', false);
});

test('another user cannot target someone else collection', function () {
    $foreign = Collection::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('test-cases.store'), draftPayload(['collection_id' => $foreign->getKey()]))
        ->assertNotFound();

    $this->assertDatabaseCount('test_cases', 0);
});
