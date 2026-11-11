<x-app-layout :title="$monitor->name" :subtitle="$monitor->type->label().' · '.$monitor->target()">
    <x-slot:actions>
        <a href="{{ route('monitors.index') }}" class="btn-secondary hidden sm:inline-flex">Kembali</a>
    </x-slot:actions>

    @php
        $pending = $monitor->status !== \App\Enums\MonitorStatus::Down && $monitor->consecutive_failures > 0;
        $sslTone = null;
        if ($sslDays !== null) {
            $sslTone = $sslDays <= 7 ? 'rose' : ($sslDays <= 14 ? 'amber' : ($sslDays <= config('monitoring.ssl_warning_days', 30) ? 'yellow' : 'emerald'));
        }
        $sslBadgeClass = match ($sslTone) {
            'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'yellow' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };
    @endphp

    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('monitors.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 sm:hidden">← Monitor</a>
                <x-type-badge :type="$monitor->type" />
                <x-status-badge :status="$monitor->status" :pending="$pending" />
                @unless ($monitor->is_active)
                    <span class="badge bg-slate-100 text-slate-700 ring-slate-400/30">Nonaktif</span>
                @endunless
            </div>

            <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ $monitor->name }}</h2>

            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                @if ($monitor->type !== \App\Enums\MonitorType::Port)
                    <a href="{{ $monitor->url }}" target="_blank" rel="noopener noreferrer" class="break-all font-mono text-teal-700 hover:text-teal-800 hover:underline">{{ $monitor->url }}</a>
                @else
                    <span class="font-mono text-slate-700">{{ $monitor->target() }}</span>
                @endif
                <span>·</span>
                <span>{{ $monitor->server?->name ?? 'Tanpa server' }}</span>
                <span>·</span>
                <span>Diperiksa {{ \App\Support\Format::relative($monitor->last_checked_at) }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('monitors.check', $monitor) }}" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                <button type="submit" :disabled="submitting" class="btn-secondary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" :class="submitting && 'animate-spin'">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span x-text="submitting ? 'Memeriksa…' : 'Periksa sekarang'">Periksa sekarang</span>
                </button>
            </form>
            <a href="{{ route('monitors.edit', $monitor) }}" class="btn-secondary">Ubah</a>
            <form method="POST" action="{{ route('monitors.destroy', $monitor) }}" onsubmit="return confirm('Hapus monitor ini beserta seluruh riwayat pemeriksaan dan insidennya?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Hapus</button>
            </form>
        </div>
    </div>

    @if ($openIncident)
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-rose-900">Insiden sedang berlangsung</p>
                        <p class="mt-0.5 text-xs text-rose-700">{{ $openIncident->cause ?: 'Tidak ada detail penyebab.' }}</p>
                    </div>
                </div>
                <div class="text-xs text-rose-700">
                    <p class="font-semibold">Sejak {{ \App\Support\Format::datetime($openIncident->started_at) }}</p>
                    <p class="text-rose-700">Durasi {{ \App\Support\Format::duration($openIncident->durationInSeconds()) }}</p>
                </div>
            </div>
        </div>
    @endif

    @if ($sslDays !== null && $sslDays <= config('monitoring.ssl_warning_days', 30))
        <div class="mb-5 flex flex-wrap items-center justify-between gap-2 rounded-xl border {{ $sslDays <= 7 ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-amber-200 bg-amber-50 text-amber-800' }} px-4 py-3 text-sm">
            <p class="font-semibold">
                Sertifikat SSL {{ $sslDays < 0 ? 'sudah kedaluwarsa' : 'akan kedaluwarsa dalam '.$sslDays.' hari' }}
                <span class="font-normal">({{ \App\Support\Format::date($monitor->ssl_expires_at) }})</span>
            </p>
            <span class="text-xs">Segera perbarui sertifikat.</span>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat-card label="Uptime 24 jam" :value="\App\Support\Format::percent($uptime['24h']['uptime'])" :hint="$uptime['24h']['total'].' cek'" tone="emerald" />
        <x-stat-card label="Uptime 7 hari" :value="\App\Support\Format::percent($uptime['7d']['uptime'])" :hint="$uptime['7d']['total'].' cek'" />
        <x-stat-card label="Uptime 30 hari" :value="\App\Support\Format::percent($uptime['30d']['uptime'])" :hint="$uptime['30d']['total'].' cek'" />
        <x-stat-card label="Rata-rata respons" :value="\App\Support\Format::responseTime($uptime['24h']['avg_ms'])" hint="24 jam" tone="teal" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="card p-4 sm:p-5 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Waktu respons & uptime</h3>
                    <p class="text-xs text-slate-500">Rentang {{ $rangeLabel }} · {{ count($chart['labels']) }} titik</p>
                </div>
                <div class="flex rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs font-semibold">
                    @foreach (['24h' => '24 jam', '7d' => '7 hari', '30d' => '30 hari'] as $key => $label)
                        <a
                            href="{{ route('monitors.show', ['monitor' => $monitor, 'range' => $key]) }}"
                            @if ($range === $key) aria-current="page" @endif
                            class="inline-flex min-h-11 items-center rounded-md px-3 py-1.5 transition {{ $range === $key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                        >{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 h-56">
                <canvas id="monitorResponseChart" role="img" aria-label="Grafik waktu respons monitor untuk rentang {{ $rangeLabel }}"></canvas>
            </div>

            <div class="mt-4 border-t border-slate-100 pt-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Uptime per titik</p>
                <div class="mt-2 h-28">
                    <canvas id="monitorUptimeChart" role="img" aria-label="Grafik uptime monitor per titik waktu untuk rentang {{ $rangeLabel }}"></canvas>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-4 sm:p-5">
                <h3 class="text-sm font-bold text-slate-900">Konfigurasi</h3>
                <dl class="mt-3 space-y-2.5 text-xs">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Jenis</dt>
                        <dd class="font-semibold text-slate-800">{{ $monitor->type->label() }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Server</dt>
                        <dd class="text-right font-semibold text-slate-800">{{ $monitor->server?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Interval</dt>
                        <dd class="font-semibold text-slate-800">{{ \App\Support\Format::secondsToInterval($monitor->interval_seconds) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Batas waktu</dt>
                        <dd class="font-semibold text-slate-800">{{ $monitor->timeout_seconds }} detik</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Ambang gangguan</dt>
                        <dd class="font-semibold text-slate-800">{{ $monitor->confirmation_threshold }}× gagal</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Ambang pulih</dt>
                        <dd class="font-semibold text-slate-800">{{ $monitor->recovery_threshold }}× sukses</dd>
                    </div>
                    @if ($monitor->type !== \App\Enums\MonitorType::Port)
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Ekspektasi</dt>
                            <dd class="text-right font-semibold text-slate-800">
                                HTTP {{ $monitor->expected_status ?? '200–399' }}
                                @if ($monitor->expected_keyword)
                                    <span class="block font-normal text-slate-500">mengandung "{{ \Illuminate\Support\Str::limit($monitor->expected_keyword, 24) }}"</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Terakhir diperiksa</dt>
                        <dd class="text-right font-semibold text-slate-800">{{ \App\Support\Format::datetime($monitor->last_checked_at) }}</dd>
                    </div>
                </dl>

                @if ($monitor->notes)
                    <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600">{{ $monitor->notes }}</div>
                @endif
            </div>

            <div class="card p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900">Sertifikat SSL</h3>
                    @if ($sslDays !== null)
                        <span class="badge {{ $sslBadgeClass }}">{{ $sslDays < 0 ? 'kedaluwarsa' : $sslDays.' hari' }}</span>
                    @endif
                </div>

                @if ($monitor->ssl_expires_at === null)
                    <p class="mt-3 text-xs text-slate-500">
                        Belum ada data SSL. Data terbaca otomatis dari pemeriksaan HTTPS berikutnya.
                    </p>
                @else
                    <dl class="mt-3 space-y-2.5 text-xs">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Kedaluwarsa</dt>
                            <dd class="font-semibold text-slate-800">{{ \App\Support\Format::date($monitor->ssl_expires_at) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Penerbit</dt>
                            <dd class="max-w-[65%] break-words text-right font-medium text-slate-700">{{ $monitor->ssl_issuer ?? '—' }}</dd>
                        </div>
                    </dl>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Pemeriksaan terakhir</h3>
                    <p class="text-xs text-slate-500">20 hasil terbaru</p>
                </div>
                <span class="badge bg-slate-100 text-slate-600 ring-slate-500/20">{{ $uptime['24h']['total'] }} cek/24 jam</span>
            </div>

            @if ($checks->isEmpty())
                <div class="p-5">
                    <x-empty-state title="Belum ada pemeriksaan" description="Tekan “Periksa sekarang” atau tunggu siklus pemeriksaan berikutnya." />
                </div>
            @else
                <div class="max-h-[28rem] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="sticky top-0 bg-slate-50 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-2.5">Waktu</th>
                                <th scope="col" class="px-3 py-2.5">Status</th>
                                <th scope="col" class="px-3 py-2.5 text-right">HTTP</th>
                                <th scope="col" class="px-3 py-2.5 text-right">Respons</th>
                                <th scope="col" class="px-4 py-2.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($checks as $check)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-slate-600">{{ \App\Support\Format::datetime($check->checked_at) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5">
                                        <x-status-badge :status="$check->status === \App\Enums\CheckStatus::Up ? 'up' : 'down'" />
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right font-mono text-slate-600">{{ $check->http_status ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right text-slate-600">{{ \App\Support\Format::responseTime($check->response_time_ms) }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">
                                        <span class="line-clamp-1" title="{{ $check->error_message }}">{{ $check->error_message ?? '—' }}</span>
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
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Riwayat insiden</h3>
                    <p class="text-xs text-slate-500">Waktu mulai, pulih, dan durasi</p>
                </div>
                <a href="{{ route('incidents.index', ['monitor' => $monitor->id]) }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">Semua</a>
            </div>

            @if ($incidents->isEmpty())
                <div class="p-5">
                    <x-empty-state title="Tidak ada insiden" description="Monitor ini belum pernah tercatat mengalami gangguan." />
                </div>
            @else
                <div class="max-h-[28rem] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="sticky top-0 bg-slate-50 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-2.5">Mulai</th>
                                <th scope="col" class="px-3 py-2.5">Pulih</th>
                                <th scope="col" class="px-3 py-2.5">Durasi</th>
                                <th scope="col" class="px-4 py-2.5">Penyebab</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($incidents as $incident)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-slate-600">
                                        {{ \App\Support\Format::datetime($incident->started_at) }}
                                        @if ($incident->isOpen())
                                            <span class="badge ml-1 bg-rose-50 text-rose-700 ring-rose-600/20">Berlangsung</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-slate-600">{{ $incident->resolved_at ? \App\Support\Format::datetime($incident->resolved_at) : '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 font-semibold text-slate-700">{{ \App\Support\Format::duration($incident->durationInSeconds()) }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">
                                        <span class="line-clamp-1" title="{{ $incident->cause }}">{{ $incident->cause ?? '—' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chart = @json($chart);

            window.pantauChart('monitorResponseChart', {
                type: 'line',
                data: {
                    labels: chart.labels,
                    datasets: [{
                        label: 'Waktu respons',
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
                        tooltip: { callbacks: { label: (context) => context.parsed.y === null ? 'Tidak ada data' : context.parsed.y + ' ms' } },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { callback: (value) => value + ' ms' } },
                    },
                },
            });

            window.pantauChart('monitorUptimeChart', {
                type: 'bar',
                data: {
                    labels: chart.labels,
                    datasets: [{
                        label: 'Uptime',
                        data: chart.uptime,
                        backgroundColor: chart.uptime.map((value) => value === null ? 'rgba(148, 163, 184, 0.25)' : (value >= 99 ? '#10b981' : (value >= 90 ? '#f59e0b' : '#f43f5e'))),
                        borderRadius: 3,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
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
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 12, maxRotation: 0, font: { size: 9 } } },
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(148, 163, 184, 0.12)' }, ticks: { stepSize: 50, callback: (value) => value + '%', font: { size: 9 } } },
                    },
                },
            });
        });
    </script>
</x-app-layout>
