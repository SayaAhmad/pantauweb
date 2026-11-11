<?php

namespace Database\Factories;

use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Monitor>
 */
class MonitorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => null,
            'name' => Str::title(fake()->unique()->words(2, true)),
            'type' => MonitorType::Http,
            'url' => 'https://'.fake()->unique()->domainName(),
            'host' => null,
            'port' => null,
            'method' => 'GET',
            'expected_status' => 200,
            'expected_keyword' => null,
            'interval_seconds' => 300,
            'timeout_seconds' => 10,
            'confirmation_threshold' => 2,
            'recovery_threshold' => 2,
            'is_active' => true,
            'status' => MonitorStatus::Unknown,
        ];
    }

    public function port(): static
    {
        return $this->state(fn () => [
            'type' => MonitorType::Port,
            'url' => null,
            'host' => '127.0.0.1',
            'port' => 22,
            'method' => null,
            'expected_status' => null,
            'expected_keyword' => null,
        ]);
    }

    public function service(): static
    {
        return $this->state(fn () => [
            'type' => MonitorType::Service,
        ]);
    }

    public function down(): static
    {
        return $this->state(fn () => [
            'status' => MonitorStatus::Down,
            'consecutive_failures' => 2,
            'failure_streak_started_at' => now()->subMinutes(10),
            'last_checked_at' => now()->subMinute(),
            'last_error' => 'Pemeriksaan melewati batas waktu',
        ]);
    }
}
