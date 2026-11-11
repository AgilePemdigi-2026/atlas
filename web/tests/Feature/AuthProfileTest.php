<?php

use App\Models\AuthProfile;
use App\Models\Collection;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function authProfileSetup(User $user, array $attributes = []): TestCase
{
    $collection = Collection::factory()->for($user)->create();
    $testCase = $collection->testCases()->create(array_merge([
        'name' => 'Login terlindungi',
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

function authProfileScenarios(TestCase $testCase): array
{
    return ['scenarios' => $testCase->scenarios()->get()->map(fn ($scenario, $index) => [
        'scenario_id' => $scenario->getKey(),
        'scenario_url' => 'https://app.example.com/login'.($index === 0 ? '' : '?fail=1'),
    ])->all()];
}

function fakeGenerateOk(): void
{
    Http::preventStrayRequests();
    Http::fake(['*/v1/generate' => Http::response([
        'status' => 'ok',
        'data' => [
            'scenarios' => [],
            'script' => "from selenium import webdriver\n\ndef login_berhasil(driver, urls):\n    driver.get(urls[0])\n",
        ],
        'meta' => ['attempts' => 1, 'dom_source' => 'snapshot'],
    ], 200)]);
}

// F7 - Auth Profile Config
describe('F7 Auth Profile Config', function () {
    test('Generate with an auth profile', function () {
        // Given I am on the Config tab
        $user = User::factory()->create();
        $testCase = authProfileSetup($user);
        // And I choose "Auth"
        // And I add a credential row and a Login URL
        $this->actingAs($user)
            ->putJson(route('auth-profiles.upsert', $testCase->getKey()), [
                'login_url' => 'https://app.example.com/login',
                'credentials' => ['email' => 'qa@example.com', 'password' => 's3cr3t'],
            ])
            // Then the auth profile is stored for the test case
            ->assertOk()
            ->assertJson(['message' => 'Auth Profile berhasil disimpan.'])
            ->assertJsonPath('data.has_credentials', true);

        expect(AuthProfile::query()
            ->where('test_case_id', $testCase->getKey())
            ->where('login_url', 'https://app.example.com/login')
            ->exists())->toBeTrue();

        // When I press the "Generate Test" button
        fakeGenerateOk();
        $this->actingAs($user)
            // And the generated script logs in before the scenarios
            ->post(route('test-cases.scenario-urls.generate', $testCase), authProfileScenarios($testCase))
            ->assertRedirect(route('test-cases.show', $testCase));
        // And the generator request includes the login URL and credentials
        Http::assertSent(fn (Request $request) => Arr::has($request->data(), 'auth_profile'));
    });

    test('Generate without auth', function () {
        // Given I am on the Config tab
        $user = User::factory()->create();
        $testCase = authProfileSetup($user);
        // And I choose "No auth"
        // When I press the "Generate Test" button
        fakeGenerateOk();
        $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), authProfileScenarios($testCase))
            ->assertRedirect(route('test-cases.show', $testCase));
        // Then no auth profile is stored for the test case
        expect(AuthProfile::query()->where('test_case_id', $testCase->getKey())->exists())->toBeFalse();
    });

    test('Guest cannot manage an auth profile', function () {
        $testCase = authProfileSetup(User::factory()->create());

        $this->putJson(route('auth-profiles.upsert', $testCase->getKey()), [
            'login_url' => 'https://app.example.com/login',
            'credentials' => ['email' => 'qa@example.com', 'password' => 's3cr3t'],
        ])->assertUnauthorized();

        $this->deleteJson(route('auth-profiles.destroy', $testCase->getKey()))->assertUnauthorized();
    });

    test("Another user's test case auth profile is not reachable", function () {
        $testCase = authProfileSetup(User::factory()->create());

        $payload = [
            'login_url' => 'https://app.example.com/login',
            'credentials' => ['email' => 'qa@example.com', 'password' => 's3cr3t'],
        ];

        $this->actingAs(User::factory()->create())
            ->putJson(route('auth-profiles.upsert', $testCase->getKey()), $payload)
            ->assertNotFound();

        expect(AuthProfile::query()->where('test_case_id', $testCase->getKey())->exists())->toBeFalse();
    });

    test('Auth profile is stored and removed', function () {
        $user = User::factory()->create();
        $testCase = authProfileSetup($user);

        $this->actingAs($user)
            ->putJson(route('auth-profiles.upsert', $testCase->getKey()), [
                'login_url' => 'https://app.example.com/login',
                'credentials' => ['email' => 'qa@example.com', 'password' => 's3cr3t'],
            ])->assertOk();

        expect(AuthProfile::query()->where('test_case_id', $testCase->getKey())->exists())->toBeTrue();

        $this->actingAs($user)
            ->deleteJson(route('auth-profiles.destroy', $testCase->getKey()))
            ->assertOk();

        expect(AuthProfile::query()->where('test_case_id', $testCase->getKey())->exists())->toBeFalse();
    });

    test('Auth profile is stored encrypted', function () {
        $user = User::factory()->create();
        $testCase = authProfileSetup($user);

        $this->actingAs($user)
            ->putJson(route('auth-profiles.upsert', $testCase->getKey()), [
                'login_url' => 'https://app.example.com/login',
                'credentials' => ['email' => 'qa@example.com', 'password' => 's3cr3t'],
            ])->assertOk();

        // Then the stored column does not hold the plain values
        $stored = DB::table('auth_profiles')
            ->where('test_case_id', $testCase->getKey())
            ->value('credentials');

        expect($stored)->not->toContain('s3cr3t')
            ->and($stored)->not->toContain('qa@example.com');
        // And reading the profile returns the original credentials
        expect($testCase->fresh()->authProfile->credentials)->toBe([
            'email' => 'qa@example.com',
            'password' => 's3cr3t',
        ]);
    });

    test('The auth profile API never returns credential values', function () {
        $user = User::factory()->create();
        $testCase = authProfileSetup($user);

        $response = $this->actingAs($user)
            ->putJson(route('auth-profiles.upsert', $testCase->getKey()), [
                'login_url' => 'https://app.example.com/login',
                'credentials' => ['email' => 'qa@example.com', 'password' => 's3cr3t'],
            ])->assertOk();

        expect($response->getContent())->not->toContain('s3cr3t')
            ->and($response->getContent())->not->toContain('qa@example.com');
    });

    test('Generate with an auth profile sends the login url and credentials to the generator', function () {
        $user = User::factory()->create();
        $testCase = authProfileSetup($user);

        $this->actingAs($user)
            ->putJson(route('auth-profiles.upsert', $testCase->getKey()), [
                'login_url' => 'https://app.example.com/login',
                'credentials' => ['id:email' => 'qa@example.com', 'password' => 's3cr3t'],
            ])->assertOk();

        fakeGenerateOk();

        $this->actingAs($user)
            ->post(route('test-cases.scenario-urls.generate', $testCase), authProfileScenarios($testCase))
            ->assertRedirect(route('test-cases.show', $testCase));

        Http::assertSent(function (Request $request) {
            $authProfile = $request->data()['auth_profile'] ?? null;

            return $authProfile['login_url'] === 'https://app.example.com/login'
                && $authProfile['credentials'] === [
                    'id:email' => 'qa@example.com',
                    'password' => 's3cr3t',
                ];
        });
    });

    test('A test case keeps at most one auth profile', function () {
        $user = User::factory()->create();
        $testCase = authProfileSetup($user);

        $this->actingAs($user)
            ->putJson(route('auth-profiles.upsert', $testCase->getKey()), [
                'login_url' => 'https://app.example.com/login',
                'credentials' => ['email' => 'qa@example.com'],
            ])->assertOk();

        $this->actingAs($user)
            ->putJson(route('auth-profiles.upsert', $testCase->getKey()), [
                'login_url' => 'https://app.example.com/other-login',
                'credentials' => ['email' => 'other@example.com'],
            ])->assertOk();

        expect(AuthProfile::query()->where('test_case_id', $testCase->getKey())->count())->toBe(1)
            ->and($testCase->fresh()->authProfile->login_url)->toBe('https://app.example.com/other-login');
    });

    test('The Config tab renders the auth profile form', function () {
        $user = User::factory()->create();
        $testCase = authProfileSetup($user);

        $this->actingAs($user)
            ->get(route('console', ['test_case' => $testCase->getKey()]))
            ->assertOk()
            ->assertSee('Auth Profile')
            ->assertSee('No auth')
            ->assertSee('Login URL')
            ->assertSee('Tambah kredensial')
            ->assertSee('Hapus');
    });

    test('The scenario url page renders the auth profile form', function () {
        $user = User::factory()->create();
        $testCase = authProfileSetup($user);

        $this->actingAs($user)
            ->get(route('test-cases.scenario-urls', $testCase))
            ->assertOk()
            ->assertSee('Auth Profile')
            ->assertSee('No auth')
            ->assertSee('Login URL')
            ->assertSee('Tambah kredensial');
    });
});
