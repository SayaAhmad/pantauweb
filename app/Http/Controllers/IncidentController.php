<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $validated = $request->validate([
            'server' => ['nullable', 'integer'],
            'monitor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'state' => ['nullable', 'in:open,resolved'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null;

        $monitorIds = $user->monitors()->pluck('id');

        $incidents = Incident::query()
            ->with('monitor.server')
            ->whereIn('monitor_id', $monitorIds)
            ->when($validated['server'] ?? null, fn ($query, $serverId) => $query->whereHas(
                'monitor',
                fn ($inner) => $inner->where('server_id', $serverId),
            ))
            ->when($validated['monitor'] ?? null, fn ($query, $monitorId) => $query->where('monitor_id', $monitorId))
            ->when($from, fn ($query) => $query->where('started_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('started_at', '<=', $to))
            ->when(($validated['state'] ?? null) === 'open', fn ($query) => $query->whereNull('resolved_at'))
            ->when(($validated['state'] ?? null) === 'resolved', fn ($query) => $query->whereNotNull('resolved_at'))
            ->latest('started_at')
            ->paginate(20)
            ->withQueryString();

        $selectedMonitorIds = Monitor::query()
            ->whereIn('id', $monitorIds)
            ->when($validated['server'] ?? null, fn ($query, $serverId) => $query->where('server_id', $serverId))
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('incidents.index', [
            'incidents' => $incidents,
            'servers' => $user->servers()->orderBy('name')->get(),
            'monitors' => $selectedMonitorIds,
            'openCount' => Incident::query()
                ->whereIn('monitor_id', $monitorIds)
                ->whereNull('resolved_at')
                ->count(),
            'filters' => $validated,
        ]);
    }
}