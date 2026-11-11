<?php

namespace Database\Factories;

use App\Enums\CheckStatus;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CheckResult>
 */
class CheckResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'status' => CheckStatus::Up,
            'http_status' => 200,
            'response_time_ms' => fake()->numberBetween(40, 400),
            'error_message' => null,
            'checked_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => CheckStatus::Down,
            'http_status' => null,
            'response_time_ms' => null,
            'error_message' => 'Pemeriksaan melewati batas waktu',
        ]);
    }
}
