<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_registration_page(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Buat akun');
    }

    public function test_guest_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Pengguna Baru',
            'email' => 'baru@example.test',
            'password' => 'rahasia-sekali-panjang',
            'password_confirmation' => 'rahasia-sekali-panjang',
        ])->assertRedirect('/');

        $user = User::query()->where('email', 'baru@example.test')->firstOrFail();

        $this->assertSame('Pengguna Baru', $user->name);
        $this->assertFalse($user->is_admin);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_never_grants_admin_access(): void
    {
        $this->post('/register', [
            'name' => 'Pengguna Baru',
            'email' => 'baru@example.test',
            'password' => 'rahasia-sekali-panjang',
            'password_confirmation' => 'rahasia-sekali-panjang',
            'is_admin' => '1',
        ])->assertRedirect('/');

        $this->assertFalse(
            User::query()->where('email', 'baru@example.test')->firstOrFail()->is_admin
        );
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'sudah-ada@example.test']);

        $this->post('/register', [
            'name' => 'Duplikat',
            'email' => 'sudah-ada@example.test',
            'password' => 'rahasia-sekali-panjang',
            'password_confirmation' => 'rahasia-sekali-panjang',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_password_must_be_at_least_12_characters(): void
    {
        $this->post('/register', [
            'name' => 'Pendek',
            'email' => 'pendek@example.test',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'pendek@example.test']);
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->post('/register', [
            'name' => 'Beda',
            'email' => 'beda@example.test',
            'password' => 'rahasia-sekali-panjang',
            'password_confirmation' => 'rahasia-lain-panjang',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'beda@example.test']);
    }

    public function test_authenticated_user_cannot_open_registration_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/register')
            ->assertRedirect();
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/register', [])->assertSessionHasErrors();
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_login_page_links_to_registration(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Buat akun');
    }
}
