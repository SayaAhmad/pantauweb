<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'hostname' => 'srv-'.fake()->unique()->numerify('####').'.internal',
            'ip' => fake()->localIpv4(),
            'description' => fake()->sentence(),
        ];
    }
}
