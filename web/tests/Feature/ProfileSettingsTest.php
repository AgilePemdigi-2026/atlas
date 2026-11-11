<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// F18 - Profile Settings
describe('F18 Profile Settings', function () {
    test('Update account information', function () {
        // Given I am logged in
        $user = User::factory()->create();
        // When I change my name and email and save
        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('status', 'Perubahan berhasil disimpan.');
        // Then the new information is saved
        expect($user->fresh()->name)->toBe('Nama Baru')
            ->and($user->fresh()->email)->toBe('baru@example.com');
    });

    test('Reject an invalid email', function () {
        // Given I am on the Settings tab
        // When I save an invalid email
        $user = User::factory()->create();
        $oldName = $user->name;
        $oldEmail = $user->email;

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Nama Baru',
                'email' => 'bukan-email',
            ])
            // Then I should see an error for the email field
            ->assertSessionHasErrors('email');
        // And the user is unchanged
        expect($user->fresh()->name)->toBe($oldName)
            ->and($user->fresh()->email)->toBe($oldEmail);
    });

    test('Guest cannot update the profile', function () {
        // Given I am not logged in
        // When I try to update the profile
        // Then I am redirected to the "/login" page
        $this->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ])->assertRedirect(route('login'));
    });
});
