<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/users')->assertForbidden();
        $this->actingAs($user)->post('/users', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.test',
            'password' => 'rahasia-sekali-panjang',
            'password_confirmation' => 'rahasia-sekali-panjang',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.test']);
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => 'Petugas Monitoring']);

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk()
            ->assertSee('Petugas Monitoring')
            ->assertSee('Pengguna');
    }

    public function test_admin_can_view_create_and_edit_forms(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/users/create')->assertOk()->assertSee('Tambah pengguna');
        $this->actingAs($admin)->get("/users/{$admin->id}/edit")->assertOk()->assertSee('Simpan perubahan');
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Petugas Monitoring',
            'email' => 'petugas@example.test',
            'password' => 'rahasia-sekali-panjang',
            'password_confirmation' => 'rahasia-sekali-panjang',
        ])->assertRedirect('/users');

        $user = User::query()->where('email', 'petugas@example.test')->firstOrFail();

        $this->assertSame('Petugas Monitoring', $user->name);
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check('rahasia-sekali-panjang', $user->password));
    }

    public function test_admin_can_create_another_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Admin Kedua',
            'email' => 'admin2@example.test',
            'password' => 'rahasia-sekali-panjang',
            'password_confirmation' => 'rahasia-sekali-panjang',
            'is_admin' => '1',
        ])->assertRedirect('/users');

        $this->assertDatabaseHas('users', [
            'email' => 'admin2@example.test',
            'is_admin' => true,
        ]);
    }

    public function test_email_must_be_unique(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['email' => 'sudah-ada@example.test']);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Duplikat',
            'email' => 'sudah-ada@example.test',
            'password' => 'rahasia-sekali-panjang',
            'password_confirmation' => 'rahasia-sekali-panjang',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_must_be_at_least_12_characters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Pendek',
            'email' => 'pendek@example.test',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'pendek@example.test']);
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'email' => 'lama@example.test',
            'password' => 'kata-sandi-lama-panjang',
        ]);

        $this->actingAs($admin)->put("/users/{$user->id}", [
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
        ])->assertRedirect('/users');

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@example.test', $user->email);
        $this->assertTrue(Hash::check('kata-sandi-lama-panjang', $user->password));
    }

    public function test_admin_can_change_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['password' => 'kata-sandi-lama-panjang']);

        $this->actingAs($admin)->put("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'kata-sandi-baru-panjang',
            'password_confirmation' => 'kata-sandi-baru-panjang',
        ])->assertRedirect('/users');

        $this->assertTrue(Hash::check('kata-sandi-baru-panjang', $user->refresh()->password));
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->delete("/users/{$admin->id}")
            ->assertRedirect('/users')
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put("/users/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
            ])
            ->assertSessionHasErrors('is_admin');

        $this->assertTrue($admin->refresh()->is_admin);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete("/users/{$user->id}")
            ->assertRedirect('/users');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
