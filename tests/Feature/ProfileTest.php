<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_student_profile_information_is_read_only(): void
    {
        $student = User::factory()->student()->create([
            'name' => 'Original Student',
            'email' => 'student@example.com',
        ]);

        $response = $this
            ->actingAs($student)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Your name and email address are managed by the exam administrator.')
            ->assertSee('disabled', false);
    }

    public function test_student_cannot_update_profile_information_with_crafted_request(): void
    {
        $student = User::factory()->student()->create([
            'name' => 'Original Student',
            'email' => 'student@example.com',
        ]);

        $this
            ->actingAs($student)
            ->patch('/profile', [
                'name' => 'Changed Student',
                'email' => 'changed@example.com',
            ])
            ->assertRedirect('/profile');

        $student->refresh();

        $this->assertSame('Original Student', $student->name);
        $this->assertSame('student@example.com', $student->email);
    }

    public function test_profile_page_does_not_show_account_deletion(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertDontSee('Delete Account');
    }

    public function test_user_cannot_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertStatus(405);
        $this->assertNotNull($user->fresh());
    }
}
