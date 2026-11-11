<?php

namespace Tests\Feature;

use App\Enums\CheckStatus;
use App\Models\CheckResult;
use App\Models\Monitor;
use App\Monitoring\Metrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_summaries_aggregate_per_monitor_in_database(): void
    {
        $first = Monitor::factory()->create();
        $second = Monitor::factory()->create();
        $empty = Monitor::factory()->create();

        CheckResult::factory()->for($first)->create(['status' => CheckStatus::Up, 'response_time_ms' => 100, 'checked_at' => now()->subHours(3)]);
        CheckResult::factory()->for($first)->create(['status' => CheckStatus::Up, 'response_time_ms' => 200, 'checked_at' => now()->subHours(2)]);
        CheckResult::factory()->failed()->for($first)->create(['checked_at' => now()->subHour()]);

        CheckResult::factory()->for($second)->create(['status' => CheckStatus::Up, 'response_time_ms' => 300, 'checked_at' => now()->subHours(2)]);
        CheckResult::factory()->failed()->for($second)->create(['checked_at' => now()->subHour()]);

        $summaries = Metrics::summaries([$first->id, $second->id, $empty->id], now()->subDay());

        $this->assertCount(2, $summaries);
        $this->assertSame(3, $summaries[$first->id]['total']);
        $this->assertSame(2, $summaries[$first->id]['up']);
        $this->assertSame(66.67, $summaries[$first->id]['uptime']);
        $this->assertSame(150, $summaries[$first->id]['avg_ms']);
        $this->assertSame(2, $summaries[$second->id]['total']);
        $this->assertSame(50.0, $summaries[$second->id]['uptime']);
        $this->assertSame(300, $summaries[$second->id]['avg_ms']);

        $overall = Metrics::overallSummary([$first->id, $second->id, $empty->id], now()->subDay());

        $this->assertSame(5, $overall['total']);
        $this->assertSame(3, $overall['up']);
        $this->assertSame(60.0, $overall['uptime']);
        $this->assertSame(200, $overall['avg_ms']);
    }

    public function test_series_accepts_plain_database_rows(): void
    {
        $monitor = Monitor::factory()->create();
        $from = now()->subHours(2)->startOfHour();
        $to = $from->copy()->addHours(2);

        DB::table('check_results')->insert([
            'monitor_id' => $monitor->id,
            'status' => 'up',
            'response_time_ms' => 180,
            'checked_at' => $from->copy()->addMinutes(10)->format('Y-m-d H:i:s'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $series = Metrics::seriesFromRows(
            DB::table('check_results')->select('checked_at', 'status', 'response_time_ms')->orderBy('checked_at')->cursor(),
            $from,
            $to,
            2,
        );

        $this->assertSame(180, $series['avg'][0]);
        $this->assertSame(100.0, $series['uptime'][0]);
        $this->assertNull($series['avg'][1]);
    }

    public function test_range_report_combines_raw_checks_and_daily_rollups(): void
    {
        $monitor = Monitor::factory()->create();
        $from = now()->subDays(40)->startOfDay();
        $to = now()->endOfDay();
        $rawFrom = now()->subDays(30)->startOfDay();

        DB::table('daily_rollups')->insert([
            'monitor_id' => $monitor->id,
            'date' => now()->subDays(35)->toDateString(),
            'total_checks' => 10,
            'successful_checks' => 9,
            'avg_response_ms' => 200,
            'max_response_ms' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CheckResult::factory()->for($monitor)->create([
            'status' => CheckStatus::Up,
            'response_time_ms' => 100,
            'checked_at' => now()->subHour(),
        ]);

        CheckResult::factory()->failed()->for($monitor)->create([
            'checked_at' => now()->subHours(2),
        ]);

        $report = Metrics::rangeReport([$monitor->id], $from, $to, $rawFrom);
        $summary = $report['rows'][$monitor->id];

        $this->assertSame(12, $summary['total']);
        $this->assertSame(10, $summary['up']);
        $this->assertSame(2, $summary['down']);
        $this->assertSame(83.33, $summary['uptime']);
        $this->assertSame(190, $summary['avg_ms']);
        $this->assertSame(500, $summary['max_ms']);
        $this->assertSame(100, $summary['p95_ms']);

        $this->assertSame(12, $report['overall']['total']);
        $this->assertSame(83.33, $report['overall']['uptime']);
        $this->assertSame(190, $report['overall']['avg_ms']);

        $this->assertCount(41, $report['chart']['labels']);
        $this->assertSame(50.0, $report['chart']['uptime'][40]);
        $this->assertSame(100, $report['chart']['avg'][40]);
        $this->assertSame(90.0, $report['chart']['uptime'][5]);
    }
}
