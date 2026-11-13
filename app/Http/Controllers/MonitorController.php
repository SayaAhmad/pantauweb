<?php

namespace App\Http\Controllers;

use App\Enums\MonitorStatus;
use App\Http\Requests\MonitorRequest;
use App\Jobs\CheckMonitorJob;
use App\Models\Incident;
use App\Models\Monitor;
use App\Monitoring\Metrics;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitorController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type');
        $serverId = $request->query('server');
        $search = trim((string) $request->query('q'));

        $monitors = $request->user()->monitors()
            ->with('server')
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($serverId, fn ($query) => $query->where('server_id', $serverId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhere('host', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("CASE status WHEN 'down' THEN 0 WHEN 'unknown' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        $summaries = Metrics::summaries($monitors->pluck('id'), now()->subDay());
        $emptySummary = ['total' => 0, 'up' => 0, 'down' => 0, 'uptime' => null, 'avg_ms' => null, 'p95_ms' => null, 'max_ms' => null];

        $openIncidents = Incident::query()
            ->whereNull('resolved_at')
            ->whereIn('monitor_id', $monitors->pluck('id'))
            ->get()
            ->keyBy('monitor_id');

        $monitors->each(function (Monitor $monitor) use ($summaries, $emptySummary, $openIncidents): void {
            $monitor->uptime24 = $summaries->get($monitor->id, $emptySummary);
            $monitor->open_incident = $openIncidents->get($monitor->id);
        });

        return view('monitors.index', [
            'monitors' => $monitors,
            'servers' => $request->user()->servers()->orderBy('name')->get(),
            'type' => $type,
            'serverId' => $serverId,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('monitors.create', [
            'monitor' => new Monitor([
                'type' => 'http',
                'method' => 'GET',
                'interval_seconds' => 300,
                'timeout_seconds' => 10,
                'confirmation_threshold' => 2,
                'recovery_threshold' => 2,
                'is_active' => true,
            ]),
            'servers' => request()->user()->servers()->orderBy('name')->get(),
        ]);
    }

    public function store(MonitorRequest $request): RedirectResponse
    {
        $monitor = $request->user()->monitors()->create([
            ...$request->validated(),
            'status' => MonitorStatus::Unknown,
            'next_check_at' => now(),
        ]);

        return redirect()
            ->route('monitors.show', $monitor)
            ->with('status', "Monitor \"{$monitor->name}\" ditambahkan. Pemeriksaan pertama akan berjalan dalam satu menit.");
    }

    public function show(Request $request, Monitor $monitor): View
    {
        $allowedRanges = [
            '24h' => [now()->subDay(), 24, '24 jam'],
            '7d' => [now()->subDays(7), 28, '7 hari'],
            '30d' => [now()->subDays(30), 30, '30 hari'],
        ];

        $range = $request->query('range', '24h');

        if (! array_key_exists($range, $allowedRanges)) {
            $range = '24h';
        }

        [$from, $buckets, $rangeLabel] = $allowedRanges[$range];

        $results = $monitor->checkResults()
            ->where('checked_at', '>=', now()->subDays(30))
            ->get(['status', 'response_time_ms', 'checked_at', 'http_status', 'error_message']);

        $uptime = [
            '24h' => Metrics::summary($results->filter(fn ($result) => $result->checked_at->greaterThanOrEqualTo(now()->subDay()))),
            '7d' => Metrics::summary($results->filter(fn ($result) => $result->checked_at->greaterThanOrEqualTo(now()->subDays(7)))),
            '30d' => Metrics::summary($results),
        ];

        $rangeResults = $results->filter(fn ($result) => $result->checked_at->greaterThanOrEqualTo($from));

        return view('monitors.show', [
            'monitor' => $monitor,
            'uptime' => $uptime,
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'chart' => Metrics::series($rangeResults, $from, now(), $buckets),
            'checks' => $monitor->checkResults()->latest('checked_at')->limit(20)->get(),
            'incidents' => $monitor->incidents()->latest('started_at')->limit(20)->get(),
            'openIncident' => $monitor->openIncident(),
            'sslDays' => $monitor->ssl_expires_at === null
                ? null
                : (int) floor(now()->diffInDays($monitor->ssl_expires_at, false)),
        ]);
    }

    public function edit(Monitor $monitor): View
    {
        return view('monitors.edit', [
            'monitor' => $monitor,
            'servers' => request()->user()->servers()->orderBy('name')->get(),
        ]);
    }

    public function update(MonitorRequest $request, Monitor $monitor): RedirectResponse
    {
        $monitor->update($request->validated());

        return redirect()
            ->route('monitors.show', $monitor)
            ->with('status', "Monitor \"{$monitor->name}\" diperbarui.");
    }

    public function destroy(Monitor $monitor): RedirectResponse
    {
        $name = $monitor->name;
        $monitor->delete();

        return redirect()
            ->route('monitors.index')
            ->with('status', "Monitor \"{$name}\" dihapus.");
    }

    public function check(Monitor $monitor): RedirectResponse
    {
        try {
            CheckMonitorJob::dispatchSync($monitor);
        } catch (\Throwable $exception) {
            return back()->withErrors(['check' => 'Pemeriksaan gagal dijalankan: '.$exception->getMessage()]);
        }

        $monitor->refresh();
        $last = $monitor->checkResults()->latest('checked_at')->first();

        $message = "Pemeriksaan manual selesai: {$monitor->status->label()}";

        if ($last !== null) {
            $message .= ' · '.Format::responseTime($last->response_time_ms).' · '.Format::datetime($last->checked_at);
        }

        return back()->with('status', $message);
    }
}
