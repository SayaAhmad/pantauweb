<?php

namespace App\Monitoring;

use App\Enums\CheckStatus;
use App\Models\CheckResult;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class Metrics
{
    /**
     * @param  Collection<int, CheckResult>  $results
     * @return array{total: int, up: int, down: int, uptime: float|null, avg_ms: int|null, p95_ms: int|null, max_ms: int|null}
     */
    public static function summary(Collection $results): array
    {
        $total = $results->count();

        $up = $results->filter(fn (CheckResult $result) => $result->status === CheckStatus::Up);

        $responses = $up
            ->pluck('response_time_ms')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (int) $value)
            ->sort()
            ->values();

        $average = $responses->isEmpty() ? null : (int) round($responses->avg());
        $maximum = $responses->isEmpty() ? null : (int) $responses->max();

        $p95 = null;

        if (! $responses->isEmpty()) {
            $index = min($responses->count() - 1, (int) ceil($responses->count() * 0.95) - 1);
            $p95 = (int) $responses->get($index);
        }

        return [
            'total' => $total,
            'up' => $up->count(),
            'down' => $total - $up->count(),
            'uptime' => $total > 0 ? round($up->count() / $total * 100, 2) : null,
            'avg_ms' => $average,
            'p95_ms' => $p95,
            'max_ms' => $maximum,
        ];
    }

    /**
     * Rangkaian waktu untuk grafik: rata-rata waktu respons dan uptime per bucket.
     *
     * @param  Collection<int, CheckResult>  $results
     * @return array{labels: array<int, string>, avg: array<int, int|null>, uptime: array<int, float|null>}
     */
    public static function series(Collection $results, CarbonInterface $from, CarbonInterface $to, int $buckets = 24): array
    {
        $fromTs = $from->getTimestamp();
        $toTs = $to->getTimestamp();
        $span = max(1, $toTs - $fromTs);
        $size = max(1, (int) ceil($span / max(1, $buckets)));
        $count = max(1, (int) ceil($span / $size));

        $total = array_fill(0, $count, 0);
        $upCount = array_fill(0, $count, 0);
        $sumResponse = array_fill(0, $count, 0);
        $responseCount = array_fill(0, $count, 0);

        foreach ($results as $result) {
            $timestamp = $result->checked_at->getTimestamp();

            if ($timestamp < $fromTs || $timestamp > $toTs) {
                continue;
            }

            $index = min($count - 1, max(0, (int) floor(($timestamp - $fromTs) / $size)));

            $total[$index]++;

            if ($result->status === CheckStatus::Up) {
                $upCount[$index]++;

                if ($result->response_time_ms !== null) {
                    $sumResponse[$index] += $result->response_time_ms;
                    $responseCount[$index]++;
                }
            }
        }

        $timezone = config('monitoring.display_timezone');
        $labels = [];
        $average = [];
        $uptime = [];

        for ($index = 0; $index < $count; $index++) {
            $bucketStart = Carbon::createFromTimestamp($fromTs + ($index * $size))
                ->timezone($timezone);

            $labels[] = $span <= 172800
                ? $bucketStart->format('H:i')
                : $bucketStart->translatedFormat('d M');

            $average[] = $responseCount[$index] > 0
                ? (int) round($sumResponse[$index] / $responseCount[$index])
                : null;

            $uptime[] = $total[$index] > 0
                ? round($upCount[$index] / $total[$index] * 100, 1)
                : null;
        }

        return [
            'labels' => $labels,
            'avg' => $average,
            'uptime' => $uptime,
        ];
    }

    /**
     * Rangkaian harian untuk laporan.
     *
     * @param  Collection<int, CheckResult>  $results
     * @return array{labels: array<int, string>, avg: array<int, int|null>, uptime: array<int, float|null>}
     */
    public static function dailySeries(Collection $results, CarbonInterface $from, CarbonInterface $to): array
    {
        $grouped = $results->groupBy(
            fn (CheckResult $result) => $result->checked_at
                ->timezone(config('monitoring.display_timezone'))
                ->toDateString()
        );

        $labels = [];
        $average = [];
        $uptime = [];

        $cursor = Carbon::parse($from->toDateString());
        $end = Carbon::parse($to->toDateString());

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $label = $cursor->translatedFormat('d M');
            $rows = $grouped->get($key, collect());

            $summary = self::summary($rows);

            $labels[] = $label;
            $average[] = $summary['avg_ms'];
            $uptime[] = $summary['uptime'];

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'avg' => $average,
            'uptime' => $uptime,
        ];
    }
}
