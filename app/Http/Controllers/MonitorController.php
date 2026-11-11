<?php

namespace App\Http\Controllers;

use App\Enums\MonitorStatus;
use App\Http\Requests\MonitorRequest;
use App\Jobs\CheckMonitorJob;
use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Server;
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

        $monitors = Monitor::query()
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

        $results = CheckResult::query()
            ->whereIn('monitor_id', $monitors->pluck('id'))
            ->where('checked_at', '>=', now()->subDay())
            ->get(['monitor_id', 'status', 'response_time_ms', 'checked_at']);

        $grouped = $results->groupBy('monitor_id');

        $openIncidents = Incident::query()
            ->whereNull('resolved_at')
            ->whereIn('monitor_id', $monitors->pluck('id'))
            ->get()
            ->keyBy('monitor_id');

        $monitors->each(function (Monitor $monitor) use ($grouped, $openIncidents): void {
            $monitor->uptime24 = Metrics::summary($grouped->get($monitor->id, collect()));
            $monitor->open_incident = $openIncidents->get($monitor->id);
        });

        return view('monitors.index', [
            'monitors' => $monitors,
            'servers' => Server::query()->orderBy('name')->get(),
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
            'servers' => Server::query()->orderBy('name')->get(),
        ]);
    }

    public function store(MonitorRequest $request): RedirectResponse
    {
        $monitor = Monitor::query()->create([
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
            'servers' => Server::query()->orderBy('name')->get(),
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
