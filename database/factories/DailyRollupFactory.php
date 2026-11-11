<?php

namespace Database\Factories;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\DailyRollup>
 */
class DailyRollupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'date' => now()->toDateString(),
            'total_checks' => 288,
            'successful_checks' => 288,
            'avg_response_ms' => 150,
            'max_response_ms' => 420,
        ];
    }
}
