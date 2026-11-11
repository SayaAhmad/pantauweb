<x-app-layout title="Insiden" subtitle="Riwayat gangguan dan durasinya">
    <div class="mb-4 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat-card label="Insiden terbuka" :value="$openCount" :tone="$openCount > 0 ? 'rose' : 'slate'" />
        <x-stat-card label="Total (terfilter)" :value="$incidents->total()" hint="berdasarkan filter" />
        <x-stat-card label="Halaman ini" :value="$incidents->count()" :hint="'dari '.$incidents->total()" tone="teal" />
        <x-stat-card label="Rentang data mentah" :value="config('monitoring.retention_days').' hari'" hint="retensi" />
    </div>

    <form method="GET" action="{{ route('incidents.index') }}" class="card mb-4 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <label for="server" class="label text-xs">Server</label>
                <select id="server" name="server" class="input">
                    <option value="">Semua</option>
                    @foreach ($servers as $server)
                        <option value="{{ $server->id }}" @selected((string) ($filters['server'] ?? '') === (string) $server->id)>{{ $server->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="monitor" class="label text-xs">Monitor</label>
                <select id="monitor" name="monitor" class="input">
                    <option value="">Semua</option>
                    @foreach ($monitors as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['monitor'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="state" class="label text-xs">Status</label>
                <select id="state" name="state" class="input">
                    <option value="">Semua</option>
                    <option value="open" @selected(($filters['state'] ?? '') === 'open')>Berlangsung</option>
                    <option value="resolved" @selected(($filters['state'] ?? '') === 'resolved')>Pulih</option>
                </select>
            </div>
            <div>
                <label for="from" class="label text-xs">Dari tanggal</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="input">
            </div>
            <div>
                <label for="to" class="label text-xs">Sampai tanggal</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="input">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary flex-1">Terapkan</button>
                <a href="{{ route('incidents.index') }}" class="btn-ghost">Reset</a>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        @if ($incidents->isEmpty())
            <div class="p-5">
                <x-empty-state title="Tidak ada insiden" description="Belum ada insiden yang cocok dengan filter ini." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3 sm:px-5">Monitor</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Mulai</th>
                            <th class="px-4 py-3">Pulih</th>
                            <th class="px-4 py-3 text-right">Durasi</th>
                            <th class="px-4 py-3">Penyebab</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($incidents as $incident)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-4 py-3 sm:px-5">
                                    <a href="{{ route('monitors.show', $incident->monitor) }}" class="group block">
                                        <span class="flex items-center gap-2">
                                            <span class="truncate font-semibold text-slate-900 group-hover:text-teal-700">{{ $incident->monitor->name }}</span>
                                        </span>
                                        <span class="mt-0.5 flex items-center gap-1.5 text-[11px] text-slate-500">
                                            <x-type-badge :type="$incident->monitor->type" />
                                            <span>{{ $incident->monitor->server?->name ?? 'Tanpa server' }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($incident->isOpen())
                                        <span class="badge bg-rose-50 text-rose-700 ring-rose-600/20">
                                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-500"></span>
                                            Berlangsung
                                        </span>
                                    @else
                                        <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Pulih
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ \App\Support\Format::datetime($incident->started_at) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $incident->resolved_at ? \App\Support\Format::datetime($incident->resolved_at) : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-700">{{ \App\Support\Format::duration($incident->durationInSeconds()) }}</td>
                                <td class="max-w-xs px-4 py-3 text-xs text-slate-500">
                                    <span class="line-clamp-2" title="{{ $incident->cause }}">{{ $incident->cause ?? '—' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                {{ $incidents->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
