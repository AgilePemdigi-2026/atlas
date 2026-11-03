<?php

namespace Tests\Feature;

use App\Models\AuthProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthProfileTest extends TestCase
{
    use RefreshDatabase;

    private function createTestCaseFor(User $user)
    {
        $collection = $user->collections()->create([
            'name' => 'Collection Testing F7',
        ]);

        return $collection->testCases()->create([
            'name' => 'Login Website Test',
            'user_story' => 'As a user, I want to log in.',
            'acceptance_criteria' => 'Given valid credentials, login succeeds.',
            'base_url' => 'https://example.com',
        ]);
    }

    public function test_user_can_save_encrypted_auth_profile(): void
    {
        $user = User::factory()->create();
        $testCase = $this->createTestCaseFor($user);

        $response = $this->actingAs($user)->putJson(
            "/test-cases/{$testCase->getKey()}/auth-profile",
            [
                'login_url' => 'https://example.com/login',
                'credentials' => [
                    'username' => 'testuser',
                    'password' => 'dummy-password-123',
                ],
            ]
        );

        $response->assertOk()
            ->assertJsonMissing(['dummy-password-123']);

        $profile = AuthProfile::where(
            'test_case_id',
            $testCase->getKey()
        )->firstOrFail();

        // Laravel harus bisa mendekripsi data melalui model.
        $this->assertSame(
            'dummy-password-123',
            $profile->credentials['password']
        );

        // Database tidak boleh menyimpan password dalam plaintext.
        $raw = DB::table('auth_profiles')
            ->where('auth_profile_id', $profile->getKey())
            ->value('credentials');

        $this->assertStringNotContainsString(
            'dummy-password-123',
            $raw
        );
    }

    public function test_user_can_update_auth_profile_without_duplicate(): void
    {
        $user = User::factory()->create();
        $testCase = $this->createTestCaseFor($user);

        $url = "/test-cases/{$testCase->getKey()}/auth-profile";

        $this->actingAs($user)->putJson($url, [
            'login_url' => 'https://example.com/login',
            'credentials' => ['username' => 'old-user'],
        ])->assertOk();

        $this->actingAs($user)->putJson($url, [
            'login_url' => 'https://example.com/new-login',
            'credentials' => ['username' => 'new-user'],
        ])->assertOk();

        $this->assertDatabaseCount('auth_profiles', 1);

        $this->assertSame(
            'new-user',
            $testCase->authProfile()->firstOrFail()
                ->credentials['username']
        );
    }

    public function test_user_can_remove_auth_profile(): void
    {
        $user = User::factory()->create();
        $testCase = $this->createTestCaseFor($user);

        $url = "/test-cases/{$testCase->getKey()}/auth-profile";

        $this->actingAs($user)->putJson($url, [
            'login_url' => 'https://example.com/login',
            'credentials' => ['username' => 'tester'],
        ])->assertOk();

        $this->actingAs($user)
            ->deleteJson($url)
            ->assertOk();

        $this->assertDatabaseCount('auth_profiles', 0);
    }

    public function test_user_cannot_modify_another_users_auth_profile(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $testCase = $this->createTestCaseFor($owner);

        $url = "/test-cases/{$testCase->getKey()}/auth-profile";

        $this->actingAs($otherUser)->putJson($url, [
            'login_url' => 'https://example.com/login',
            'credentials' => ['username' => 'unauthorized'],
        ])->assertNotFound();

        $this->actingAs($otherUser)
            ->deleteJson($url)
            ->assertNotFound();

        $this->assertDatabaseCount('auth_profiles', 0);
    }

    public function test_guest_cannot_save_auth_profile(): void
    {
        $this->putJson('/test-cases/1/auth-profile', [
            'login_url' => 'https://example.com/login',
            'credentials' => ['username' => 'tester'],
        ])->assertUnauthorized();
    }
}