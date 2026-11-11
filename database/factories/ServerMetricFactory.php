<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ServerMetric>
 */
class ServerMetricFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'cpu_percent' => fake()->randomFloat(2, 5, 80),
            'ram_percent' => fake()->randomFloat(2, 20, 90),
            'disk_percent' => fake()->randomFloat(2, 30, 95),
            'load_average' => fake()->randomFloat(2, 0.1, 4),
            'collected_at' => now(),
        ];
    }
}
