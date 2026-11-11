<?php

namespace App\Console\Commands;

use App\Enums\CheckStatus;
use App\Models\CheckResult;
use App\Models\DailyRollup;
use App\Models\Monitor;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BuildDailyRollups extends Command
{
    protected $signature = 'monitors:rollup {--days=2 : Jumlah hari ke belakang, termasuk hari ini}';

    protected $description = 'Bangun rollup harian dari hasil pemeriksaan mentah';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $results = CheckResult::query()
            ->whereBetween('checked_at', [$start, $end])
            ->get(['monitor_id', 'status', 'response_time_ms', 'checked_at']);

        $grouped = $results->groupBy(
            fn (CheckResult $result) => $result->monitor_id.'|'.$result->checked_at->toDateString()
        );

        $monitorIds = Monitor::query()->pluck('id');

        $written = 0;

        foreach ($grouped as $key => $rows) {
            [$monitorId, $date] = explode('|', $key);

            if (! $monitorIds->contains((int) $monitorId)) {
                continue;
            }

            $summary = $this->summarize($rows);

            DailyRollup::updateOrCreate(
                ['monitor_id' => (int) $monitorId, 'date' => $date],
                [
                    'total_checks' => $summary['total'],
                    'successful_checks' => $summary['up'],
                    'avg_response_ms' => $summary['avg_ms'],
                    'max_response_ms' => $summary['max_ms'],
                ],
            );

            $written++;
        }

        $this->info("{$written} rollup harian ditulis untuk periode {$start->toDateString()} – {$end->toDateString()}.");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, CheckResult>  $rows
     * @return array{total: int, up: int, avg_ms: int|null, max_ms: int|null}
     */
    private function summarize(Collection $rows): array
    {
        $up = $rows->filter(fn (CheckResult $result) => $result->status === CheckStatus::Up);

        $responses = $up
            ->pluck('response_time_ms')
            ->filter(fn ($value) => $value !== null);

        return [
            'total' => $rows->count(),
            'up' => $up->count(),
            'avg_ms' => $responses->isEmpty() ? null : (int) round($responses->avg()),
            'max_ms' => $responses->isEmpty() ? null : (int) $responses->max(),
        ];
    }
}
