<?php

namespace App\Http\Controllers;

use App\Enums\MonitorStatus;
use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Server;
use App\Monitoring\Metrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $type = $request->query('type');
        $serverId = $request->query('server');

        $monitors = Monitor::query()
            ->with('server')
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($serverId, fn ($query) => $query->where('server_id', $serverId))
            ->get()
            ->sortBy(fn (Monitor $monitor) => [
                match ($monitor->status) {
                    MonitorStatus::Down => 0,
                    MonitorStatus::Unknown => 1,
                    MonitorStatus::Up => 2,
                },
                $monitor->name,
            ])
            ->values();

        $since = now()->subDay();

        $results = CheckResult::query()
            ->whereIn('monitor_id', $monitors->pluck('id'))
            ->where('checked_at', '>=', $since)
            ->get(['monitor_id', 'status', 'response_time_ms', 'checked_at']);

        $grouped = $results->groupBy('monitor_id');

        $monitors->each(function (Monitor $monitor) use ($grouped): void {
            $monitor->uptime24 = Metrics::summary($grouped->get($monitor->id, collect()));
        });

        $summary24 = Metrics::summary($results);

        $counts = [
            'total' => $monitors->count(),
            'up' => $monitors->filter(fn (Monitor $monitor) => $monitor->status === MonitorStatus::Up)->count(),
            'down' => $monitors->filter(fn (Monitor $monitor) => $monitor->status === MonitorStatus::Down)->count(),
            'unknown' => $monitors->filter(fn (Monitor $monitor) => $monitor->status === MonitorStatus::Unknown)->count(),
        ];

        $incidentBase = Incident::query()
            ->with('monitor')
            ->when($serverId, fn ($query) => $query->whereHas('monitor', fn ($inner) => $inner->where('server_id', $serverId)))
            ->when($type, fn ($query) => $query->whereHas('monitor', fn ($inner) => $inner->where('type', $type)));

        $openIncidents = (clone $incidentBase)
            ->whereNull('resolved_at')
            ->latest('started_at')
            ->get();

        $recentIncidents = (clone $incidentBase)
            ->latest('started_at')
            ->limit(6)
            ->get();

        $sslAlerts = $monitors
            ->filter(fn (Monitor $monitor) => $monitor->ssl_expires_at !== null
                && $monitor->ssl_expires_at->lessThanOrEqualTo(now()->addDays(config('monitoring.ssl_warning_days', 30))))
            ->sortBy('ssl_expires_at')
            ->values();

        return view('dashboard', [
            'monitors' => $monitors,
            'counts' => $counts,
            'summary24' => $summary24,
            'openIncidents' => $openIncidents,
            'recentIncidents' => $recentIncidents,
            'sslAlerts' => $sslAlerts,
            'servers' => Server::query()->orderBy('name')->get(),
            'chart' => Metrics::series($results, $since, now(), 24),
            'type' => $type,
            'serverId' => $serverId,
        ]);
    }
}
