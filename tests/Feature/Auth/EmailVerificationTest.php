<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_is_disabled(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertNotFound();
    }

    public function test_unverified_user_can_access_dashboard(): void
    {
        $user = User::factory()->unverified()->student()->create();

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk();
    }
}
