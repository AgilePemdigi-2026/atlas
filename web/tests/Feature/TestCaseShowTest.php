<?php

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function completedTestCase(User $user): array
{
    $testCase = Collection::factory()->for($user)->create()->testCases()->create([
        'name' => 'Login saya',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'status' => 'completed',
    ]);
    $testCase->scenarios()->create(['name' => 'Login berhasil', 'scenario_url' => 'https://app.example.com/login']);
    $testCase->scenarios()->create(['name' => 'Login gagal']);
    $output = $testCase->llmOutput()->create([
        'output' => [
            'scenarios' => [],
            'meta' => ['attempts' => 2, 'dom_source' => 'live', 'model' => 'gpt-6-luna'],
        ],
        'validation_status' => 'valid',
        'retry_count' => 2,
    ]);
    $output->generatedScript()->create([
        'content' => "from selenium import webdriver\n\ndef login_berhasil(driver, urls):\n    driver.get(urls[0])\n",
        'status' => 'completed',
    ]);

    return [$testCase, $output];
}

test('the owner sees the script and scenario urls on the console', function () {
    $user = User::factory()->create();
    [$testCase] = completedTestCase($user);

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $testCase->getKey()]))
        ->assertOk()
        ->assertSee('Login saya')
        ->assertSee('https://app.example.com/login')
        ->assertSee('from selenium import webdriver')
        ->assertSee('Scenario URL')
        ->assertSee('Generated Script');
});

test('a draft without output tells the user the script is not ready', function () {
    $user = User::factory()->create();
    $testCase = Collection::factory()->for($user)->create()->testCases()->create([
        'name' => 'Not generated yet',
        'user_story' => 'Sebagai pengguna, saya ingin masuk.',
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $testCase->getKey()]))
        ->assertOk()
        ->assertSee('No Test Case results yet.');
});

test('the console opens the requested result tab', function () {
    $user = User::factory()->create();
    [$testCase] = completedTestCase($user);

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $testCase->getKey(), 'tab' => 'scenario']))
        ->assertOk()
        ->assertSee('data-active-tab="scenario"', false);

    $this->actingAs($user)
        ->get(route('console', ['test_case' => $testCase->getKey(), 'tab' => 'script']))
        ->assertOk()
        ->assertSee('data-active-tab="script"', false);
});

test('the old scenario-urls and show pages redirect to the console', function () {
    $user = User::factory()->create();
    [$testCase] = completedTestCase($user);

    $this->actingAs($user)
        ->get(route('test-cases.scenario-urls', $testCase))
        ->assertRedirect(route('console', [
            'test_case' => $testCase->getKey(),
            'collection' => $testCase->collection_id,
            'tab' => 'scenario',
        ]));

    $this->actingAs($user)
        ->get(route('test-cases.show', $testCase))
        ->assertRedirect(route('console', [
            'test_case' => $testCase->getKey(),
            'collection' => $testCase->collection_id,
            'tab' => 'script',
        ]));
});

test('guests are redirected and foreign owners are forbidden', function () {
    [$testCase] = completedTestCase(User::factory()->create());

    $this->get(route('test-cases.show', $testCase))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())
        ->get(route('test-cases.show', $testCase))
        ->assertForbidden();
});
