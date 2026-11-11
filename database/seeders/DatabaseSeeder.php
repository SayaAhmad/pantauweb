<?php

namespace Database\Seeders;

use App\Models\Monitor;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@pantau.test')],
            [
                'name' => env('ADMIN_NAME', 'Admin Pantau'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ],
        );

        $production = Server::query()->firstOrCreate(
            ['name' => 'Server Produksi'],
            [
                'hostname' => 'prod-01.internal',
                'ip' => '10.0.0.11',
                'description' => 'Server utama untuk website dan API.',
            ],
        );

        $staging = Server::query()->firstOrCreate(
            ['name' => 'Server Staging'],
            [
                'hostname' => 'staging-01.internal',
                'ip' => '10.0.1.21',
                'description' => 'Lingkungan uji sebelum rilis.',
            ],
        );

        $monitors = [
            [
                'name' => 'Website Utama',
                'type' => 'http',
                'url' => 'https://example.com',
                'server_id' => $production->id,
                'interval_seconds' => 300,
                'expected_status' => 200,
                'notes' => 'Halaman publik utama.',
            ],
            [
                'name' => 'API Gateway',
                'type' => 'service',
                'url' => 'https://httpbin.org/status/200',
                'server_id' => $production->id,
                'interval_seconds' => 300,
                'expected_status' => 200,
                'notes' => 'Endpoint kesehatan API.',
            ],
            [
                'name' => 'SSH Produksi',
                'type' => 'port',
                'host' => '127.0.0.1',
                'port' => 22,
                'server_id' => $production->id,
                'interval_seconds' => 300,
                'notes' => 'Akses administratif server produksi.',
            ],
            [
                'name' => 'Database PostgreSQL',
                'type' => 'port',
                'host' => '127.0.0.1',
                'port' => 5432,
                'server_id' => $production->id,
                'interval_seconds' => 300,
                'notes' => 'Basis data aplikasi utama.',
            ],
            [
                'name' => 'Website Marketing',
                'type' => 'http',
                'url' => 'https://www.google.com',
                'server_id' => $staging->id,
                'interval_seconds' => 300,
                'expected_status' => 200,
                'notes' => 'Halaman kampanye.',
            ],
        ];

        foreach ($monitors as $attributes) {
            Monitor::query()->firstOrCreate(
                ['name' => $attributes['name']],
                $attributes,
            );
        }
    }
}
