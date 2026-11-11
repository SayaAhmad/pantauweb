<x-app-layout title="Dashboard" subtitle="Ringkasan status semua monitor">
    <x-slot:actions>
        <a href="{{ route('monitors.create') }}" class="btn-primary">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="hidden sm:inline">Tambah monitor</span>
        </a>
    </x-slot:actions>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2">
            <select name="type" class="input w-auto min-w-36 py-1.5 text-xs" onchange="this.form.submit()">
                <option value="">Semua jenis</option>
                @foreach (\App\Enums\MonitorType::cases() as $typeOption)
                    <option value="{{ $typeOption->value }}" @selected($type === $typeOption->value)>{{ $typeOption->label() }}</option>
                @endforeach
            </select>
            <select name="server" class="input w-auto min-w-40 py-1.5 text-xs" onchange="this.form.submit()">
                <option value="">Semua server</option>
                @foreach ($servers as $server)
                    <option value="{{ $server->id }}" @selected((string) $serverId === (string) $server->id)>{{ $server->name }}</option>
                @endforeach
            </select>
            @if ($type || $serverId)
                <a href="{{ route('dashboard') }}" class="btn-ghost py-1.5 text-xs">Reset</a>
            @endif
        </form>

        <p class="text-xs text-slate-500">Diperbarui {{ now()->timezone(config('monitoring.display_timezone'))->translatedFormat('d M Y H:i') }}</p>
    </div>

    @if ($openIncidents->isNotEmpty())
        <div class="mb-5 overflow-hidden rounded-2xl border border-rose-200 bg-rose-50">
            <div class="flex items-center gap-2 border-b border-rose-200/70 px-4 py-2.5">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
                </span>
                <p class="text-xs font-bold uppercase tracking-wide text-rose-700">Insiden berlangsung</p>
            </div>
            <ul class="divide-y divide-rose-200/60">
                @foreach ($openIncidents as $incident)
                    <li>
                        <a href="{{ route('monitors.show', $incident->monitor) }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 transition hover:bg-rose-100/60">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-rose-900">{{ $incident->monitor->name }}</p>
                                <p class="mt-0.5 line-clamp-1 text-xs text-rose-700/80">{{ $incident->cause ?: 'Tidak ada detail penyebab' }}</p>
                            </div>
                            <div class="text-right text-xs text-rose-700">
                                <p class="font-semibold">Berlangsung {{ \App\Support\Format::duration($incident->durationInSeconds()) }}</p>
                                <p class="text-rose-600/80">sejak {{ \App\Support\Format::datetime($incident->started_at) }}</p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat-card
            label="Monitor"
            :value="$counts['total']"
            :hint="$counts['total'] > 0 ? $counts['up'].' normal' : 'kosong'"
        />
        <x-stat-card
            label="Uptime 24 jam"
            :value="\App\Support\Format::percent($summary24['uptime'])"
            :hint="$summary24['total'].' cek'"
            tone="emerald"
        />
        <x-stat-card
            label="Rata-rata respons"
            :value="\App\Support\Format::responseTime($summary24['avg_ms'])"
            hint="24 jam"
            tone="teal"
        />
        <x-stat-card
            label="Insiden terbuka"
            :value="$openIncidents->count()"
            :hint="$counts['down'].' monitor gangguan'"
            :tone="$openIncidents->isNotEmpty() ? 'rose' : 'slate'"
        />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="card p-4 sm:p-5 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Waktu respons</h2>
                    <p class="text-xs text-slate-500">Rata-rata per jam, 24 jam terakhir</p>
                </div>
                <span class="badge bg-teal-50 text-teal-700 ring-teal-600/20">24 jam</span>
            </div>
            <div class="mt-4 h-64">
                <canvas id="responseChart"></canvas>
            </div>
        </div>

        <div class="card flex flex-col p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Masa berlaku SSL</h2>
                <span class="badge {{ $sslAlerts->isNotEmpty() ? 'bg-amber-50 text-amber-700 ring-amber-600/20' : 'bg-slate-100 text-slate-600 ring-slate-500/20' }}">
                    {{ $sslAlerts->count() }} perhatian
                </span>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">Sertifikat kedaluwarsa ≤ 30 hari</p>

            <div class="mt-3 flex-1">
                @if ($sslAlerts->isEmpty())
                    <div class="flex h-full min-h-32 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 text-center">
                        <p class="text-xs font-medium text-slate-500">Semua sertifikat aman</p>
                        <p class="mt-0.5 text-[11px] text-slate-400">Tidak ada yang mendekati kedaluwarsa.</p>
                    </div>
                @else
                    <ul class="space-y-2">
                        @foreach ($sslAlerts as $monitor)
                            @php
                                $days = (int) floor(now()->diffInDays($monitor->ssl_expires_at, false));
                                $sslBadgeClass = match (true) {
                                    $days <= 7 => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    $days <= 14 => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                    default => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                                };
                            @endphp
                            <li>
                                <a href="{{ route('monitors.show', $monitor) }}" class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 transition hover:border-slate-300 hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-semibold text-slate-800">{{ $monitor->name }}</p>
                                        <p class="text-[11px] text-slate-500">{{ \App\Support\Format::date($monitor->ssl_expires_at) }}</p>
                                    </div>
                                    <span class="badge shrink-0 {{ $sslBadgeClass }}">
                                        {{ $days < 0 ? 'kedaluwarsa' : $days.' hari' }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="card overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5 sm:px-5">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Status monitor</h2>
                    <p class="text-xs text-slate-500">{{ $counts['up'] }} normal · {{ $counts['down'] }} gangguan · {{ $counts['unknown'] }} belum diperiksa</p>
                </div>
                <a href="{{ route('monitors.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">Lihat semua</a>
            </div>

            @if ($monitors->isEmpty())
                <div class="p-5">
                    <x-empty-state title="Belum ada monitor" description="Tambahkan website, port, atau layanan pertama untuk mulai memantau." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/70">
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-2.5 sm:px-5">Monitor</th>
                                <th class="px-4 py-2.5">Status</th>
                                <th class="px-4 py-2.5 text-right">Uptime</th>
                                <th class="px-4 py-2.5 text-right">Respons</th>
                                <th class="px-4 py-2.5 text-right">Terakhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($monitors as $monitor)
                                @php
                                    $pending = $monitor->status !== \App\Enums\MonitorStatus::Down && $monitor->consecutive_failures > 0;
                                @endphp
                                <tr class="transition hover:bg-slate-50/70">
                                    <td class="px-4 py-3 sm:px-5">
                                        <a href="{{ route('monitors.show', $monitor) }}" class="group flex items-start gap-2.5">
                                            <span class="mt-0.5">
                                                <x-type-badge :type="$monitor->type" />
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate font-semibold text-slate-900 group-hover:text-teal-700">{{ $monitor->name }}</span>
                                                <span class="block truncate text-[11px] text-slate-500">{{ \Illuminate\Support\Str::limit($monitor->target(), 46) }}</span>
                                                @if ($monitor->server)
                                                    <span class="block text-[11px] text-slate-400">{{ $monitor->server->name }}</span>
                                                @endif
                                            </span>
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <x-status-badge :status="$monitor->status" :pending="$pending" />
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-700">
                                        {{ \App\Support\Format::percent($monitor->uptime24['uptime'], 1) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600">
                                        {{ \App\Support\Format::responseTime($monitor->last_response_ms) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-xs text-slate-500" title="{{ \App\Support\Format::datetime($monitor->last_checked_at) }}">
                                        {{ \App\Support\Format::relative($monitor->last_checked_at) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5">
                <h2 class="text-sm font-bold text-slate-900">Insiden terbaru</h2>
                <a href="{{ route('incidents.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">Semua</a>
            </div>

            @if ($recentIncidents->isEmpty())
                <div class="px-4 py-8 text-center">
                    <p class="text-xs text-slate-500">Belum ada insiden tercatat.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($recentIncidents as $incident)
                        <li>
                            <a href="{{ route('monitors.show', $incident->monitor) }}" class="block px-4 py-3 transition hover:bg-slate-50">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-xs font-semibold text-slate-800">{{ $incident->monitor->name }}</p>
                                    @if ($incident->isOpen())
                                        <span class="badge shrink-0 bg-rose-50 text-rose-700 ring-rose-600/20">Berlangsung</span>
                                    @else
                                        <span class="badge shrink-0 bg-emerald-50 text-emerald-700 ring-emerald-600/20">Pulih</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-[11px] text-slate-500">
                                    {{ \App\Support\Format::datetime($incident->started_at) }}
                                    · {{ \App\Support\Format::duration($incident->durationInSeconds()) }}
                                </p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chart = @json($chart);

            window.pantauChart('responseChart', {
                type: 'line',
                data: {
                    labels: chart.labels,
                    datasets: [{
                        label: 'Rata-rata respons',
                        data: chart.avg,
                        borderColor: '#0d9488',
                        backgroundColor: 'rgba(13, 148, 136, 0.10)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        spanGaps: true,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => context.parsed.y === null ? 'Tidak ada data' : context.parsed.y + ' ms',
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.16)' },
                            ticks: { callback: (value) => value + ' ms' },
                        },
                    },
                },
            });
        });
    </script>
</x-app-layout>
