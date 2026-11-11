<?php

namespace Database\Factories;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Incident>
 */
class IncidentFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = now()->subMinutes(30);

        return [
            'monitor_id' => Monitor::factory(),
            'started_at' => $startedAt,
            'resolved_at' => now(),
            'duration_seconds' => 1800,
            'cause' => 'Pemeriksaan melewati batas waktu',
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'resolved_at' => null,
            'duration_seconds' => null,
        ]);
    }
}
