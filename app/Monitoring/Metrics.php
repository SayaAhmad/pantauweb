<?php

namespace App\Monitoring;

use App\Enums\CheckStatus;
use App\Models\CheckResult;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class Metrics
{
    /**
     * Ringkasan uptime 24 jam per monitor, diagregasi di database.
     *
     * @param  iterable<int, int>  $monitorIds
     * @return Collection<int, array{total: int, up: int, down: int, uptime: float|null, avg_ms: int|null, p95_ms: int|null, max_ms: int|null}>
     */
    public static function summaries(iterable $monitorIds, CarbonInterface $since): Collection
    {
        $ids = collect($monitorIds)->all();

        if ($ids === []) {
            return collect();
        }

        return DB::table('check_results')
            ->selectRaw('monitor_id, COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up_count")
            ->selectRaw("SUM(CASE WHEN status = 'up' AND response_time_ms IS NOT NULL THEN response_time_ms ELSE 0 END) as response_sum")
            ->selectRaw("SUM(CASE WHEN status = 'up' AND response_time_ms IS NOT NULL THEN 1 ELSE 0 END) as response_count")
            ->whereIn('monitor_id', $ids)
            ->where('checked_at', '>=', $since)
            ->groupBy('monitor_id')
            ->get()
            ->mapWithKeys(function (object $row): array {
                $total = (int) $row->total;
                $up = (int) $row->up_count;
                $responseCount = (int) $row->response_count;

                return [(int) $row->monitor_id => [
                    'total' => $total,
                    'up' => $up,
                    'down' => $total - $up,
                    'uptime' => $total > 0 ? round($up / $total * 100, 2) : null,
                    'avg_ms' => $responseCount > 0 ? (int) round(((int) $row->response_sum) / $responseCount) : null,
                    'p95_ms' => null,
                    'max_ms' => null,
                ]];
            });
    }

    /**
     * Ringkasan gabungan seluruh monitor pada rentang tertentu.
     *
     * @param  iterable<int, int>  $monitorIds
     * @return array{total: int, up: int, down: int, uptime: float|null, avg_ms: int|null, p95_ms: int|null, max_ms: int|null}
     */
    public static function overallSummary(iterable $monitorIds, CarbonInterface $since): array
    {
        $ids = collect($monitorIds)->all();

        if ($ids === []) {
            return self::summary(collect());
        }

        $row = DB::table('check_results')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up_count")
            ->selectRaw("SUM(CASE WHEN status = 'up' AND response_time_ms IS NOT NULL THEN response_time_ms ELSE 0 END) as response_sum")
            ->selectRaw("SUM(CASE WHEN status = 'up' AND response_time_ms IS NOT NULL THEN 1 ELSE 0 END) as response_count")
            ->whereIn('monitor_id', $ids)
            ->where('checked_at', '>=', $since)
            ->first();

        $total = (int) ($row->total ?? 0);
        $up = (int) ($row->up_count ?? 0);
        $responseCount = (int) ($row->response_count ?? 0);

        return [
            'total' => $total,
            'up' => $up,
            'down' => $total - $up,
            'uptime' => $total > 0 ? round($up / $total * 100, 2) : null,
            'avg_ms' => $responseCount > 0 ? (int) round(((int) $row->response_sum) / $responseCount) : null,
            'p95_ms' => null,
            'max_ms' => null,
        ];
    }

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
        return self::seriesFromRows($results, $from, $to, $buckets);
    }

    /**
     * Rangkaian waktu dari iterable ringan (query builder atau model).
     *
     * @param  iterable<object>  $results
     * @return array{labels: array<int, string>, avg: array<int, int|null>, uptime: array<int, float|null>}
     */
    public static function seriesFromRows(iterable $results, CarbonInterface $from, CarbonInterface $to, int $buckets = 24): array
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
            $checkedAt = $result->checked_at;
            $timestamp = $checkedAt instanceof \DateTimeInterface
                ? $checkedAt->getTimestamp()
                : (int) strtotime((string) $checkedAt);

            if ($timestamp < $fromTs || $timestamp > $toTs) {
                continue;
            }

            $index = min($count - 1, max(0, (int) floor(($timestamp - $fromTs) / $size)));

            $total[$index]++;

            $status = $result->status instanceof CheckStatus ? $result->status->value : $result->status;

            if ($status === CheckStatus::Up->value) {
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
     * Ringkasan laporan banyak monitor, diagregasi di database.
     * Data mentah yang masih tersimpan digabung rollup harian untuk hari yang lebih lama.
     *
     * @param  iterable<int, int>  $monitorIds
     * @return array{
     *     rows: Collection<int, array{total: int, up: int, down: int, uptime: float|null, avg_ms: int|null, p95_ms: int|null, max_ms: int|null}>,
     *     overall: array{total: int, up: int, down: int, uptime: float|null, avg_ms: int|null, p95_ms: int|null, max_ms: int|null},
     *     chart: array{labels: array<int, string>, avg: array<int, int|null>, uptime: array<int, float|null>}
     * }
     */
    public static function rangeReport(iterable $monitorIds, CarbonInterface $from, CarbonInterface $to, CarbonInterface $rawFrom): array
    {
        $ids = collect($monitorIds)->all();

        if ($ids === []) {
            return [
                'rows' => collect(),
                'overall' => self::summary(collect()),
                'chart' => self::emptyChart($from, $to),
            ];
        }

        $raw = DB::table('check_results')
            ->selectRaw('monitor_id, COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up_count")
            ->selectRaw("SUM(CASE WHEN status = 'up' AND response_time_ms IS NOT NULL THEN response_time_ms ELSE 0 END) as response_sum")
            ->selectRaw("SUM(CASE WHEN status = 'up' AND response_time_ms IS NOT NULL THEN 1 ELSE 0 END) as response_count")
            ->selectRaw("MAX(CASE WHEN status = 'up' THEN response_time_ms END) as max_response")
            ->whereIn('monitor_id', $ids)
            ->whereBetween('checked_at', [$rawFrom, $to])
            ->groupBy('monitor_id')
            ->get()
            ->keyBy('monitor_id');

        $rollups = DB::table('daily_rollups')
            ->selectRaw('monitor_id, SUM(total_checks) as total')
            ->selectRaw('SUM(successful_checks) as up_count')
            ->selectRaw('SUM(COALESCE(avg_response_ms, 0) * successful_checks) as response_sum')
            ->selectRaw('SUM(CASE WHEN avg_response_ms IS NOT NULL THEN successful_checks ELSE 0 END) as response_count')
            ->selectRaw('MAX(max_response_ms) as max_response')
            ->whereIn('monitor_id', $ids)
            ->whereBetween('date', [$from->toDateString(), $rawFrom->copy()->subDay()->toDateString()])
            ->groupBy('monitor_id')
            ->get()
            ->keyBy('monitor_id');

        $rows = collect($ids)->mapWithKeys(function (int $id) use ($raw, $rollups, $rawFrom, $to): array {
            $rawRow = $raw->get($id);
            $rollupRow = $rollups->get($id);

            $rawResponses = (int) ($rawRow->response_count ?? 0);
            $total = (int) ($rawRow->total ?? 0) + (int) ($rollupRow->total ?? 0);
            $up = (int) ($rawRow->up_count ?? 0) + (int) ($rollupRow->up_count ?? 0);
            $responseSum = (int) ($rawRow->response_sum ?? 0) + (int) ($rollupRow->response_sum ?? 0);
            $responseCount = $rawResponses + (int) ($rollupRow->response_count ?? 0);
            $maxResponse = max((int) ($rawRow->max_response ?? 0), (int) ($rollupRow->max_response ?? 0));

            return [$id => [
                'total' => $total,
                'up' => $up,
                'down' => $total - $up,
                'uptime' => $total > 0 ? round($up / $total * 100, 2) : null,
                'avg_ms' => $responseCount > 0 ? (int) round($responseSum / $responseCount) : null,
                'p95_ms' => $rawResponses > 0 ? self::percentile([$id], $rawFrom, $to, 0.95, $rawResponses) : null,
                'max_ms' => $responseCount > 0 ? $maxResponse : null,
            ]];
        });

        $rawOverallCount = (int) $raw->sum('response_count');
        $overall = [
            'total' => (int) $rows->sum('total'),
            'up' => (int) $rows->sum('up'),
            'down' => (int) $rows->sum('down'),
            'uptime' => null,
            'avg_ms' => null,
            'p95_ms' => $rawOverallCount > 0 ? self::percentile($ids, $rawFrom, $to, 0.95, $rawOverallCount) : null,
            'max_ms' => null,
        ];

        $overallResponses = (int) $raw->sum('response_count') + (int) $rollups->sum('response_count');
        $overallResponseSum = (int) $raw->sum('response_sum') + (int) $rollups->sum('response_sum');
        $overallMax = max(0, (int) $raw->max('max_response'), (int) $rollups->max('max_response'));

        if ($overall['total'] > 0) {
            $overall['uptime'] = round($overall['up'] / $overall['total'] * 100, 2);
        }

        if ($overallResponses > 0) {
            $overall['avg_ms'] = (int) round($overallResponseSum / $overallResponses);
            $overall['max_ms'] = $overallMax;
        }

        return [
            'rows' => $rows,
            'overall' => $overall,
            'chart' => self::dailyChart($ids, $from, $to, $rawFrom),
        ];
    }

    /**
     * Persentil waktu respons dari data mentah.
     *
     * @param  array<int, int>  $monitorIds
     */
    private static function percentile(array $monitorIds, CarbonInterface $from, CarbonInterface $to, float $percentile, int $count): ?int
    {
        $offset = min($count - 1, max(0, (int) ceil($count * $percentile) - 1));

        $value = DB::table('check_results')
            ->whereIn('monitor_id', $monitorIds)
            ->where('status', 'up')
            ->whereNotNull('response_time_ms')
            ->whereBetween('checked_at', [$from, $to])
            ->orderBy('response_time_ms')
            ->offset($offset)
            ->limit(1)
            ->value('response_time_ms');

        return $value === null ? null : (int) $value;
    }

    /**
     * Grafik harian gabungan data mentah dan rollup.
     *
     * @param  array<int, int>  $monitorIds
     * @return array{labels: array<int, string>, avg: array<int, int|null>, uptime: array<int, float|null>}
     */
    private static function dailyChart(array $monitorIds, CarbonInterface $from, CarbonInterface $to, CarbonInterface $rawFrom): array
    {
        $rawRows = DB::table('check_results')
            ->selectRaw(self::localDateExpression($from).' as bucket, COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up_count")
            ->selectRaw("SUM(CASE WHEN status = 'up' AND response_time_ms IS NOT NULL THEN response_time_ms ELSE 0 END) as response_sum")
            ->selectRaw("SUM(CASE WHEN status = 'up' AND response_time_ms IS NOT NULL THEN 1 ELSE 0 END) as response_count")
            ->whereIn('monitor_id', $monitorIds)
            ->whereBetween('checked_at', [$rawFrom, $to])
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $rollupRows = DB::table('daily_rollups')
            ->selectRaw('date, SUM(total_checks) as total, SUM(successful_checks) as up_count')
            ->selectRaw('SUM(COALESCE(avg_response_ms, 0) * successful_checks) as response_sum')
            ->selectRaw('SUM(CASE WHEN avg_response_ms IS NOT NULL THEN successful_checks ELSE 0 END) as response_count')
            ->whereIn('monitor_id', $monitorIds)
            ->whereBetween('date', [$from->toDateString(), $rawFrom->copy()->subDay()->toDateString()])
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $average = [];
        $uptime = [];

        $cursor = Carbon::parse($from->toDateString());
        $end = Carbon::parse($to->toDateString());

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $rawRow = $rawRows->get($key);
            $rollupRow = $rollupRows->get($key);

            $total = (int) ($rawRow->total ?? 0) + (int) ($rollupRow->total ?? 0);
            $up = (int) ($rawRow->up_count ?? 0) + (int) ($rollupRow->up_count ?? 0);
            $responseSum = (int) ($rawRow->response_sum ?? 0) + (int) ($rollupRow->response_sum ?? 0);
            $responseCount = (int) ($rawRow->response_count ?? 0) + (int) ($rollupRow->response_count ?? 0);

            $labels[] = $cursor->translatedFormat('d M');
            $average[] = $responseCount > 0 ? (int) round($responseSum / $responseCount) : null;
            $uptime[] = $total > 0 ? round($up / $total * 100, 1) : null;

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'avg' => $average,
            'uptime' => $uptime,
        ];
    }

    /**
     * @return array{labels: array<int, string>, avg: array<int, int|null>, uptime: array<int, float|null>}
     */
    private static function emptyChart(CarbonInterface $from, CarbonInterface $to): array
    {
        $labels = [];
        $average = [];
        $uptime = [];

        $cursor = Carbon::parse($from->toDateString());
        $end = Carbon::parse($to->toDateString());

        while ($cursor->lessThanOrEqualTo($end)) {
            $labels[] = $cursor->translatedFormat('d M');
            $average[] = null;
            $uptime[] = null;
            $cursor->addDay();
        }

        return ['labels' => $labels, 'avg' => $average, 'uptime' => $uptime];
    }

    private static function localDateExpression(CarbonInterface $reference): string
    {
        $minutes = (int) $reference->copy()->timezone(config('monitoring.display_timezone'))->utcOffset();
        $modifier = $minutes >= 0 ? '+'.$minutes : (string) $minutes;

        return match (DB::connection()->getDriverName()) {
            'sqlite' => "date(checked_at, '{$modifier} minutes')",
            'pgsql' => "(checked_at + interval '{$minutes} minutes')::date",
            default => "DATE(DATE_ADD(checked_at, INTERVAL {$minutes} MINUTE))",
        };
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
