<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@pantau.test');
        $password = (string) env('ADMIN_PASSWORD');

        if ($password === '') {
            $this->command?->warn('ADMIN_PASSWORD belum diatur; pembuatan admin dilewati.');

            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Admin Pantau'),
                'password' => Hash::make($password),
                'is_admin' => true,
            ],
        );

        if (! $user->is_admin) {
            $user->update(['is_admin' => true]);
        }

        $this->command?->info(
            $user->wasRecentlyCreated
                ? "Admin dibuat: {$email}"
                : "Admin sudah ada: {$email} (kata sandi tidak diubah)"
        );
    }
}
