<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_buttons_are_visible_on_login_and_register_pages(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('auth.google'))
            ->assertSee('Masuk dengan Google');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('auth.google'))
            ->assertSee('Daftar dengan Google');
    }

    public function test_redirect_to_google_oauth_provider(): void
    {
        $response = $this->get(route('auth.google'));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', (string) $response->headers->get('Location'));
    }

    public function test_new_user_can_register_and_login_with_google(): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-12345');
        $googleUser->shouldReceive('getName')->andReturn('Budi Google');
        $googleUser->shouldReceive('getEmail')->andReturn('budi@example.com');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'google_id' => 'google-id-12345',
            'name' => 'Budi Google',
            'password' => null,
            'is_admin' => false,
        ]);

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_user_auto_links_google_id_on_login(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'google_id' => null,
            'password' => 'secret123456',
        ]);

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-99999');
        $googleUser->shouldReceive('getName')->andReturn('Existing User Google');
        $googleUser->shouldReceive('getEmail')->andReturn('existing@example.com');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'existing@example.com',
            'google_id' => 'google-id-99999',
        ]);

        $this->assertAuthenticatedAs($existingUser->fresh());
    }

    public function test_google_login_handles_oauth_failure_gracefully(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andThrow(new \Exception('OAuth Access Denied'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_login_fails_when_email_is_missing(): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-no-email');
        $googleUser->shouldReceive('getEmail')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_user_dashboard_only_sees_own_monitors(): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-isolated');
        $googleUser->shouldReceive('getName')->andReturn('Isolated User');
        $googleUser->shouldReceive('getEmail')->andReturn('isolated@example.com');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // 1. Login via Google callback
        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

        $user = User::where('email', 'isolated@example.com')->firstOrFail();

        // 2. Create monitor for this user and another monitor for a different user
        $ownMonitor = \App\Models\Monitor::factory()->create([
            'user_id' => $user->id,
            'name' => 'Monitor Milik Google User',
        ]);

        $otherUser = User::factory()->create();
        $otherMonitor = \App\Models\Monitor::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Monitor Milik Pengguna Lain',
        ]);

        // 3. Visit dashboard as the Google-authenticated user
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Monitor Milik Google User')
            ->assertDontSee('Monitor Milik Pengguna Lain');
    }
}
