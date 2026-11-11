<?php

namespace Tests\Feature;

use App\Enums\CheckStatus;
use App\Models\CheckResult;
use App\Models\Monitor;
use App\Monitoring\Metrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uptime_is_calculated_from_all_checks_and_responses_only_from_successes(): void
    {
        $monitor = Monitor::factory()->create();

        CheckResult::factory()->for($monitor)->create([
            'status' => CheckStatus::Up,
            'response_time_ms' => 100,
            'checked_at' => now()->subHours(3),
        ]);
        CheckResult::factory()->for($monitor)->create([
            'status' => CheckStatus::Up,
            'response_time_ms' => 200,
            'checked_at' => now()->subHours(2),
        ]);
        CheckResult::factory()->failed()->for($monitor)->create([
            'checked_at' => now()->subHour(),
        ]);

        $summary = Metrics::summary($monitor->checkResults()->get());

        $this->assertSame(3, $summary['total']);
        $this->assertSame(2, $summary['up']);
        $this->assertSame(1, $summary['down']);
        $this->assertSame(66.67, $summary['uptime']);
        $this->assertSame(150, $summary['avg_ms']);
        $this->assertSame(200, $summary['p95_ms']);
        $this->assertSame(200, $summary['max_ms']);
    }

    public function test_summary_without_checks_returns_null_uptime(): void
    {
        $summary = Metrics::summary(collect());

        $this->assertSame(0, $summary['total']);
        $this->assertNull($summary['uptime']);
        $this->assertNull($summary['avg_ms']);
    }

    public function test_series_buckets_results_and_keeps_empty_buckets_as_null(): void
    {
        $monitor = Monitor::factory()->create();
        $from = now()->subHours(2)->startOfHour();
        $to = $from->copy()->addHours(2);

        CheckResult::factory()->for($monitor)->create([
            'status' => CheckStatus::Up,
            'response_time_ms' => 180,
            'checked_at' => $from->copy()->addMinutes(10),
        ]);

        $series = Metrics::series($monitor->checkResults()->get(), $from, $to, 2);

        $this->assertCount(2, $series['labels']);
        $this->assertSame(180, $series['avg'][0]);
        $this->assertSame(100.0, $series['uptime'][0]);
        $this->assertNull($series['avg'][1]);
        $this->assertNull($series['uptime'][1]);
    }
}
