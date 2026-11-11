<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/monitors')->assertRedirect('/login');
        $this->get('/reports')->assertRedirect('/login');
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'rahasia-sekali',
        ]);

        $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'rahasia-sekali',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'rahasia-sekali',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'salah',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Dashboard');
    }
}
