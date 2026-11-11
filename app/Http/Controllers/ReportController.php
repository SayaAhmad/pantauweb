<?php

namespace App\Http\Controllers;

use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Server;
use App\Monitoring\Metrics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
     *     servers: \Illuminate\Support\Collection,
     *     monitorOptions: \Illuminate\Support\Collection,
     *     rows: \Illuminate\Support\Collection,
     *     overall: array{total: int, up: int, down: int, uptime: float|null, avg_ms: int|null, p95_ms: int|null, max_ms: int|null},
     *     incidentCount: int,
     *     downtimeSeconds: int,
     *     chart: array{labels: array<int, string>, avg: array<int, int|null>, uptime: array<int, float|null>},
     *     filters: array<string, mixed>
     * }
     */
    private function reportData(Request $request): array
    {
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

        $monitors = Monitor::query()
            ->with('server')
            ->when($serverId, fn ($query) => $query->where('server_id', $serverId))
            ->when($monitorId, fn ($query) => $query->whereKey($monitorId))
            ->orderBy('name')
            ->get();

        $monitorIds = $monitors->pluck('id');

        $results = CheckResult::query()
            ->whereIn('monitor_id', $monitorIds)
            ->whereBetween('checked_at', [$from, $to])
            ->get(['monitor_id', 'status', 'response_time_ms', 'checked_at']);

        $grouped = $results->groupBy('monitor_id');

        $incidents = Incident::query()
            ->whereIn('monitor_id', $monitorIds)
            ->whereBetween('started_at', [$from, $to])
            ->get(['monitor_id', 'started_at', 'resolved_at', 'duration_seconds']);

        $incidentsByMonitor = $incidents->groupBy('monitor_id');

        $rows = $monitors->map(function (Monitor $monitor) use ($grouped, $incidentsByMonitor): array {
            $summary = Metrics::summary($grouped->get($monitor->id, collect()));
            $monitorIncidents = $incidentsByMonitor->get($monitor->id, collect());

            $downtime = $monitorIncidents->sum(function (Incident $incident): int {
                if ($incident->resolved_at === null) {
                    return (int) $incident->started_at->diffInSeconds(now(), true);
                }

                return (int) ($incident->duration_seconds ?? 0);
            });

            return [
                'monitor' => $monitor,
                'summary' => $summary,
                'incident_count' => $monitorIncidents->count(),
                'downtime_seconds' => $downtime,
            ];
        });

        $serverOptions = Server::query()->orderBy('name')->get();

        $monitorOptions = Monitor::query()
            ->when($serverId, fn ($query) => $query->where('server_id', $serverId))
            ->orderBy('name')
            ->pluck('name', 'id');

        return [
            'from' => $from,
            'to' => $to,
            'servers' => $serverOptions,
            'monitorOptions' => $monitorOptions,
            'rows' => $rows,
            'overall' => Metrics::summary($results),
            'incidentCount' => $incidents->count(),
            'downtimeSeconds' => (int) $rows->sum('downtime_seconds'),
            'chart' => Metrics::dailySeries($results, $from, $to),
            'filters' => [
                'server' => $serverId,
                'monitor' => $monitorId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }
}
