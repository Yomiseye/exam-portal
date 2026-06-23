<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_has_password_visibility_toggle(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('showPassword', false)
            ->assertSee('Show password', false)
            ->assertSee('Hide password', false)
            ->assertSee('autocomplete="current-password"', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('student.dashboard', absolute: false));
    }

    public function test_login_rotates_the_active_session_token(): void
    {
        $user = User::factory()->create([
            'active_session_token' => 'previous-token',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHas('active_session_token');

        $this->assertAuthenticated();
        $this->assertNotSame('previous-token', $user->fresh()->active_session_token);
        $this->assertNotNull($user->fresh()->active_session_token);
    }

    public function test_current_active_session_can_access_authenticated_routes(): void
    {
        $user = User::factory()->student()->create([
            'active_session_token' => 'current-session-token',
        ]);

        $this->withSession(['active_session_token' => 'current-session-token'])
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_stale_active_session_is_logged_out(): void
    {
        $user = User::factory()->student()->create([
            'active_session_token' => 'new-session-token',
        ]);

        $this->withSession(['active_session_token' => 'old-session-token'])
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
