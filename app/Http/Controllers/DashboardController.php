<?php

namespace App\Http\Controllers;

use App\Enums\MonitorStatus;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\User;
use App\Monitoring\Metrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->is_admin) {
            return $this->adminDashboard();
        }

        $type = $request->query('type');
        $serverId = $request->query('server');

        $monitors = $user->monitors()
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
        $monitorIds = $monitors->pluck('id');
        $emptySummary = ['total' => 0, 'up' => 0, 'down' => 0, 'uptime' => null, 'avg_ms' => null, 'p95_ms' => null, 'max_ms' => null];

        $summaries = Metrics::summaries($monitorIds, $since);

        $monitors->each(function (Monitor $monitor) use ($summaries, $emptySummary): void {
            $monitor->uptime24 = $summaries->get($monitor->id, $emptySummary);
        });

        $summary24 = Metrics::overallSummary($monitorIds, $since);

        $counts = [
            'total' => $monitors->count(),
            'up' => $monitors->filter(fn (Monitor $monitor) => $monitor->status === MonitorStatus::Up)->count(),
            'down' => $monitors->filter(fn (Monitor $monitor) => $monitor->status === MonitorStatus::Down)->count(),
            'unknown' => $monitors->filter(fn (Monitor $monitor) => $monitor->status === MonitorStatus::Unknown)->count(),
        ];

        $incidentBase = Incident::query()
            ->with('monitor')
            ->whereIn('monitor_id', $monitorIds);

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
            'servers' => $user->servers()->orderBy('name')->get(),
            'chart' => Metrics::seriesFromRows(
                DB::table('check_results')
                    ->select('checked_at', 'status', 'response_time_ms')
                    ->whereIn('monitor_id', $monitorIds)
                    ->where('checked_at', '>=', $since)
                    ->orderBy('checked_at')
                    ->cursor(),
                $since,
                now(),
                24,
            ),
            'type' => $type,
            'serverId' => $serverId,
        ]);
    }

    private function adminDashboard(): View
    {
        $users = User::query()->orderBy('name')->get();

        return view('admin.dashboard', [
            'users' => $users,
            'stats' => [
                'users' => $users->count(),
                'admins' => User::query()->where('is_admin', true)->count(),
            ],
        ]);
    }
}
