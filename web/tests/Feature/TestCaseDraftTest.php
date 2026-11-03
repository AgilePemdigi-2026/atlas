<?php

use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    $response = $this->actingAs($user)
        ->post(route('test-cases.store'), draftPayload(['collection_id' => $collection->getKey()]));

    $testCase = TestCase::query()->sole();
    $response->assertRedirect(route('test-cases.scenario-urls', $testCase));
    expect($testCase->status->value)->toBe('pending')
        ->and($testCase->collection_id)->toBe($collection->getKey())
        ->and($testCase->scenarios()->pluck('name')->all())->toBe(['Login berhasil']);
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
        'acceptance_criteria' => 'Given saya di beranda Then saya melihat judul',
    ]));

    expect(TestCase::query()->sole()->scenarios()->pluck('name')->all())->toBe(['Checkout']);
});

test('the required fields are enforced', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('test-cases.store'), draftPayload(['user_story' => '']))
        ->assertSessionHasErrors('user_story');
});

test('the console shows the user story form posting to the draft endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('console'))
        ->assertOk()
        ->assertSee(route('test-cases.store'), false)
        ->assertSee('Generate Test')
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
