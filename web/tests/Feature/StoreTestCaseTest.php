<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTestCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_test_case(): void
    {
        $user = User::factory()->create();

        $collection = $user->collections()->create([
            'name' => 'Testing Collection',
        ]);

        $response = $this->actingAs($user)->postJson('/test-cases', [
            'collection_id' => $collection->getKey(),
            'name' => 'Login Test',
            'user_story' => 'As a user, I want to log in.',
            'acceptance_criteria' => 'Given valid credentials, Then login succeeds.',
            'base_url' => 'https://example.com',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('test_cases', [
            'collection_id' => $collection->getKey(),
            'name' => 'Login Test',
            'acceptance_criteria' => 'Given valid credentials, Then login succeeds.',
        ]);
    }

    public function test_user_story_and_acceptance_criteria_are_required(): void
    {
        $user = User::factory()->create();

        $collection = $user->collections()->create([
            'name' => 'Testing Collection',
        ]);

        $response = $this->actingAs($user)->postJson('/test-cases', [
            'collection_id' => $collection->getKey(),
            'name' => 'Login Test',
            'user_story' => '',
            'acceptance_criteria' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'user_story',
                'acceptance_criteria',
            ]);
    }

    public function test_user_cannot_use_another_users_collection(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $collection = $anotherUser->collections()->create([
            'name' => 'Private Collection',
        ]);

        $response = $this->actingAs($user)->postJson('/test-cases', [
            'collection_id' => $collection->getKey(),
            'name' => 'Unauthorized Test',
            'user_story' => 'As a user...',
            'acceptance_criteria' => 'Given...',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('test_cases', [
            'name' => 'Unauthorized Test',
        ]);
    }

    public function test_guest_cannot_create_test_case(): void
    {
        $response = $this->postJson('/test-cases', [
            'collection_id' => 1,
            'name' => 'Guest Test',
            'user_story' => 'As a user...',
            'acceptance_criteria' => 'Given...',
        ]);

        $response->assertUnauthorized();
    }
}