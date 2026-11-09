<?php

use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buatTestCase(User $user, ?string $isiSkrip = '# skrip uji'): TestCase
{
    $testCase = $user->collections()->create(['name' => 'Toko'])
        ->testCases()->create(['name' => 'Keranjang belanja', 'user_story' => 'Cerita.']);

    if ($isiSkrip !== null) {
        $testCase->llmOutput()->create(['output' => '{}'])
            ->generatedScript()->create(['content' => $isiSkrip, 'status' => 'completed']);
    }

    return $testCase;
}

it('shows the script of the selected test case to its owner', function () {
    $user = User::factory()->create();
    $testCase = buatTestCase($user);

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $testCase->test_case_id]))
        ->assertOk()
        ->assertSee('# skrip uji')
        ->assertSee('test_keranjang_belanja.py');
});

it('does not show the script of another user', function () {
    $testCase = buatTestCase(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->get(route('console', ['test_case' => $testCase->test_case_id]))
        ->assertOk()
        ->assertDontSee('# skrip uji');
});

it('shows an empty message when the test case has no script', function () {
    $user = User::factory()->create();
    $testCase = buatTestCase($user, null);

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $testCase->test_case_id]))
        ->assertOk()
        ->assertSee('Belum ada hasil test case');
});

it('lets the owner download the script', function () {
    $user = User::factory()->create();
    $testCase = buatTestCase($user);

    $response = $this->actingAs($user)
        ->get(route('test-cases.script.download', $testCase))
        ->assertOk()
        ->assertDownload('test_keranjang_belanja.py');

    expect($response->streamedContent())->toBe('# skrip uji');
});

it('forbids downloading the script of another user', function () {
    $testCase = buatTestCase(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->get(route('test-cases.script.download', $testCase))
        ->assertForbidden();
});

it('returns 404 when downloading a test case without a script', function () {
    $user = User::factory()->create();
    $testCase = buatTestCase($user, null);

    $this->actingAs($user)
        ->get(route('test-cases.script.download', $testCase))
        ->assertNotFound();
});

it('redirects guests to the login page', function () {
    $testCase = buatTestCase(User::factory()->create());

    $this->get(route('test-cases.script.download', $testCase))
        ->assertRedirect(route('login'));
});
