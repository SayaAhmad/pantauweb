@php
    $exportQuery = array_filter([
        'server' => $filters['server'],
        'monitor' => $filters['monitor'],
        'from' => $filters['from'],
        'to' => $filters['to'],
    ], fn ($value) => $value !== null && $value !== '');

    $uptimeColor = function (?float $value): string {
        if ($value === null) {
            return 'text-slate-600';
        }

        return $value >= 99.5 ? 'text-emerald-700' : ($value >= 95 ? 'text-amber-700' : 'text-rose-700');
    };
@endphp

<x-app-layout title="Laporan" subtitle="Uptime, waktu respons, dan insiden per monitor">
    <x-slot:actions>
        <button type="button" class="btn-secondary no-print" onclick="window.print()" aria-label="Cetak laporan">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Z" />
            </svg>
            <span class="hidden sm:inline">Cetak</span>
        </button>
        <a href="{{ route('reports.export', $exportQuery) }}" class="btn-primary" aria-label="Ekspor laporan sebagai CSV">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            <span class="hidden sm:inline">Ekspor CSV</span>
        </a>
    </x-slot:actions>

    <form method="GET" action="{{ route('reports.index') }}" class="card no-print mb-4 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="server" class="label text-xs">Server</label>
                <select id="server" name="server" class="input">
                    <option value="">Semua server</option>
                    @foreach ($servers as $server)
                        <option value="{{ $server->id }}" @selected((string) $filters['server'] === (string) $server->id)>{{ $server->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="monitor" class="label text-xs">Monitor</label>
                <select id="monitor" name="monitor" class="input">
                    <option value="">Semua monitor</option>
                    @foreach ($monitorOptions as $id => $name)
                        <option value="{{ $id }}" @selected((string) $filters['monitor'] === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="label text-xs">Dari tanggal</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] }}" class="input">
            </div>
            <div>
                <label for="to" class="label text-xs">Sampai tanggal</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] }}" class="input">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary flex-1">Terapkan</button>
                <a href="{{ route('reports.index') }}" class="btn-ghost">Reset</a>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-slate-500">Rentang maksimum 90 hari. Data mentah tersedia {{ config('monitoring.retention_days') }} hari terakhir; rollup harian disimpan permanen.</p>
    </form>

    <div class="mb-4 hidden print:block">
        <h1 class="text-xl font-extrabold text-slate-900">Laporan Pantau</h1>
        <p class="text-sm text-slate-600">{{ \App\Support\Format::date($from) }} – {{ \App\Support\Format::date($to) }}</p>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat-card
            label="Uptime agregat"
            :value="\App\Support\Format::percent($overall['uptime'])"
            :hint="$overall['total'].' cek'"
            :tone="($overall['uptime'] ?? 100) >= 99.5 ? 'emerald' : 'amber'"
        />
        <x-stat-card label="Rata-rata respons" :value="\App\Support\Format::responseTime($overall['avg_ms'])" hint="berhasil" tone="teal" />
        <x-stat-card label="p95 respons" :value="\App\Support\Format::responseTime($overall['p95_ms'])" hint="persentil 95" />
        <x-stat-card
            label="Insiden"
            :value="$incidentCount"
            :hint="'downtime '.\App\Support\Format::duration($downtimeSeconds)"
            :tone="$incidentCount > 0 ? 'rose' : 'slate'"
        />
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Uptime harian</h2>
                    <p class="text-xs text-slate-500">{{ \App\Support\Format::date($from) }} – {{ \App\Support\Format::date($to) }}</p>
                </div>
                <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-600/20">%</span>
            </div>
            <div class="mt-4 h-56">
                <canvas id="reportUptimeChart" role="img" aria-label="Grafik uptime harian dari {{ \App\Support\Format::date($from) }} sampai {{ \App\Support\Format::date($to) }}"></canvas>
            </div>
        </div>

        <div class="card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Waktu respons harian</h2>
                    <p class="text-xs text-slate-500">Rata-rata pemeriksaan berhasil</p>
                </div>
                <span class="badge bg-teal-50 text-teal-700 ring-teal-600/20">ms</span>
            </div>
            <div class="mt-4 h-56">
                <canvas id="reportResponseChart" role="img" aria-label="Grafik waktu respons rata-rata harian dari pemeriksaan berhasil"></canvas>
            </div>
        </div>
    </div>

    <div class="card mt-4 overflow-hidden print-plain">
        <div class="border-b border-slate-200 px-4 py-3.5 sm:px-5">
            <h2 class="text-sm font-bold text-slate-900">Ringkasan per monitor</h2>
            <p class="text-xs text-slate-500">{{ $rows->count() }} monitor · {{ $overall['total'] }} pemeriksaan · {{ $incidentCount }} insiden</p>
        </div>

        @if ($rows->isEmpty())
            <div class="p-5">
                <x-empty-state title="Tidak ada data" description="Tidak ada monitor atau pemeriksaan pada filter ini." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <th scope="col" class="px-4 py-3 sm:px-5">Monitor</th>
                            <th scope="col" class="px-3 py-3">Jenis</th>
                            <th scope="col" class="px-3 py-3">Server</th>
                            <th scope="col" class="px-3 py-3 text-right">Cek</th>
                            <th scope="col" class="px-3 py-3 text-right">Uptime</th>
                            <th scope="col" class="px-3 py-3 text-right">Rata-rata</th>
                            <th scope="col" class="px-3 py-3 text-right">p95</th>
                            <th scope="col" class="px-3 py-3 text-right">Maks</th>
                            <th scope="col" class="px-3 py-3 text-right">Insiden</th>
                            <th scope="col" class="px-4 py-3 text-right">Downtime</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-4 py-3 sm:px-5">
                                    <a href="{{ route('monitors.show', $row['monitor']) }}" class="font-semibold text-slate-900 hover:text-teal-700">{{ $row['monitor']->name }}</a>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3"><x-type-badge :type="$row['monitor']->type" /></td>
                                <td class="whitespace-nowrap px-3 py-3 text-xs text-slate-600">{{ $row['monitor']->server?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right text-slate-600">{{ $row['summary']['total'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-bold {{ $uptimeColor($row['summary']['uptime']) }}">{{ \App\Support\Format::percent($row['summary']['uptime'], 2) }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right text-slate-600">{{ \App\Support\Format::responseTime($row['summary']['avg_ms']) }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right text-slate-600">{{ \App\Support\Format::responseTime($row['summary']['p95_ms']) }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right text-slate-600">{{ \App\Support\Format::responseTime($row['summary']['max_ms']) }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right text-slate-600">{{ $row['incident_count'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600">{{ \App\Support\Format::duration($row['downtime_seconds']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50/70">
                        <tr class="text-sm font-bold text-slate-800">
                            <td colspan="3" class="px-4 py-3 sm:px-5">Total / agregat</td>
                            <td class="px-3 py-3 text-right">{{ $overall['total'] }}</td>
                            <td class="px-3 py-3 text-right {{ $uptimeColor($overall['uptime']) }}">{{ \App\Support\Format::percent($overall['uptime'], 2) }}</td>
                            <td class="px-3 py-3 text-right">{{ \App\Support\Format::responseTime($overall['avg_ms']) }}</td>
                            <td class="px-3 py-3 text-right">{{ \App\Support\Format::responseTime($overall['p95_ms']) }}</td>
                            <td class="px-3 py-3 text-right">{{ \App\Support\Format::responseTime($overall['max_ms']) }}</td>
                            <td class="px-3 py-3 text-right">{{ $incidentCount }}</td>
                            <td class="px-4 py-3 text-right">{{ \App\Support\Format::duration($downtimeSeconds) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <p class="mt-3 no-print text-[11px] text-slate-600">
        Uptime dihitung dari jumlah pemeriksaan berhasil dibagi total pemeriksaan pada rentang. Waktu respons hanya dari pemeriksaan berhasil.
    </p>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chart = @json($chart);

            window.pantauChart('reportUptimeChart', {
                type: 'bar',
                data: {
                    labels: chart.labels,
                    datasets: [{
                        label: 'Uptime',
                        data: chart.uptime,
                        backgroundColor: chart.uptime.map((value) => value === null ? 'rgba(148, 163, 184, 0.25)' : (value >= 99 ? '#10b981' : (value >= 90 ? '#f59e0b' : '#f43f5e'))),
                        borderRadius: 4,
                        barPercentage: 0.7,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (context) => context.parsed.y === null ? 'Tidak ada data' : context.parsed.y + '% uptime' } },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxRotation: 0, maxTicksLimit: 10 } },
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { stepSize: 25, callback: (value) => value + '%' } },
                    },
                },
            });

            window.pantauChart('reportResponseChart', {
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
                        pointRadius: 2,
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
                        tooltip: { callbacks: { label: (context) => context.parsed.y === null ? 'Tidak ada data' : context.parsed.y + ' ms' } },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxRotation: 0, maxTicksLimit: 10 } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { callback: (value) => value + ' ms' } },
                    },
                },
            });
        });
    </script>
</x-app-layout>
