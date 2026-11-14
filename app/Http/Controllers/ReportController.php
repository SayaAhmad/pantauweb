<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\Server;
use App\Monitoring\Metrics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.index', $this->reportData($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->reportData($request);

        $filename = 'laporan-pantau-'.$data['from']->format('Ymd').'-'.$data['to']->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Monitor',
                'Jenis',
                'Server',
                'Total pemeriksaan',
                'Berhasil',
                'Uptime (%)',
                'Rata-rata respons (ms)',
                'p95 respons (ms)',
                'Maks respons (ms)',
                'Jumlah insiden',
                'Downtime (detik)',
            ]);

            foreach ($data['rows'] as $row) {
                fputcsv($handle, [
                    $row['monitor']->name,
                    $row['monitor']->type->label(),
                    $row['monitor']->server?->name ?? '',
                    $row['summary']['total'],
                    $row['summary']['up'],
                    $row['summary']['uptime'] === null ? '' : number_format($row['summary']['uptime'], 2, '.', ''),
                    $row['summary']['avg_ms'] ?? '',
                    $row['summary']['p95_ms'] ?? '',
                    $row['summary']['max_ms'] ?? '',
                    $row['incident_count'],
                    $row['downtime_seconds'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{
     *     from: Carbon,
     *     to: Carbon,
     *     servers: Collection,
     *     monitorOptions: Collection,
     *     rows: Collection,
     *     overall: array{total: int, up: int, down: int, uptime: float|null, avg_ms: int|null, p95_ms: int|null, max_ms: int|null},
     *     incidentCount: int,
     *     downtimeSeconds: int,
     *     chart: array{labels: array<int, string>, avg: array<int, int|null>, uptime: array<int, float|null>},
     *     filters: array<string, mixed>
     * }
     */
    private function reportData(Request $request): array
    {
        $user = $request->user();
        $validated = $request->validate([
            'server' => ['nullable', 'integer'],
            'monitor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfDay();

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : $to->copy()->subDays(6)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($from->diffInDays($to) > 90) {
            $from = $to->copy()->subDays(90)->startOfDay();
        }

        $serverId = $validated['server'] ?? null;
        $monitorId = $validated['monitor'] ?? null;

        $monitorIds = $user->monitors()
            ->when($serverId, fn ($query) => $query->where('server_id', $serverId))
            ->when($monitorId, fn ($query) => $query->whereKey($monitorId))
            ->pluck('id');

        $monitors = $user->monitors()
            ->with('server')
            ->when($serverId, fn ($query) => $query->where('server_id', $serverId))
            ->when($monitorId, fn ($query) => $query->whereKey($monitorId))
            ->orderBy('name')
            ->get();

        $retentionStart = now()->subDays((int) config('monitoring.retention_days'))->startOfDay();
        $rawFrom = $from->greaterThan($retentionStart) ? $from->copy() : $retentionStart->copy();

        if ($rawFrom->greaterThan($to)) {
            $rawFrom = $to->copy();
        }

        $range = Metrics::rangeReport($monitorIds, $from, $to, $rawFrom);

        $incidentCounts = DB::table('incidents')
            ->selectRaw('monitor_id, COUNT(*) as incident_count, SUM(COALESCE(duration_seconds, 0)) as resolved_downtime')
            ->whereIn('monitor_id', $monitorIds)
            ->whereBetween('started_at', [$from, $to])
            ->groupBy('monitor_id')
            ->get()
            ->keyBy('monitor_id');

        $openDowntime = DB::table('incidents')
            ->whereIn('monitor_id', $monitorIds)
            ->whereNull('resolved_at')
            ->whereBetween('started_at', [$from, $to])
            ->get(['monitor_id', 'started_at'])
            ->groupBy('monitor_id')
            ->map(fn ($rows) => $rows->sum(fn ($row) => (int) Carbon::parse($row->started_at)->diffInSeconds(now(), true)));

        $emptySummary = [
            'total' => 0,
            'up' => 0,
            'down' => 0,
            'uptime' => null,
            'avg_ms' => null,
            'p95_ms' => null,
            'max_ms' => null,
        ];

        $rows = $monitors->map(function (Monitor $monitor) use ($range, $incidentCounts, $openDowntime, $emptySummary): array {
            $incidentRow = $incidentCounts->get($monitor->id);

            return [
                'monitor' => $monitor,
                'summary' => $range['rows']->get($monitor->id, $emptySummary),
                'incident_count' => (int) ($incidentRow->incident_count ?? 0),
                'downtime_seconds' => (int) ($incidentRow->resolved_downtime ?? 0) + (int) $openDowntime->get($monitor->id, 0),
            ];
        });

        $serverOptions = Server::query()->whereIn('id', $user->servers()->pluck('id'))->orderBy('name')->get();

        $monitorOptions = Monitor::query()
            ->whereIn('id', $monitorIds)
            ->when($serverId, fn ($query) => $query->where('server_id', $serverId))
            ->orderBy('name')
            ->pluck('name', 'id');

        return [
            'from' => $from,
            'to' => $to,
            'servers' => $serverOptions,
            'monitorOptions' => $monitorOptions,
            'rows' => $rows,
            'overall' => $range['overall'],
            'incidentCount' => (int) $incidentCounts->sum('incident_count'),
            'downtimeSeconds' => (int) $rows->sum('downtime_seconds'),
            'chart' => $range['chart'],
            'filters' => [
                'server' => $serverId,
                'monitor' => $monitorId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }
}