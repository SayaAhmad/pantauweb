<x-app-layout title="Monitor" subtitle="Website, port, dan layanan yang dipantau">
    <x-slot:actions>
        <a href="{{ route('monitors.create') }}" class="btn-primary" aria-label="Tambah monitor">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="hidden sm:inline">Tambah monitor</span>
        </a>
    </x-slot:actions>

    <form method="GET" action="{{ route('monitors.index') }}" class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama, URL, atau host…" aria-label="Cari monitor berdasarkan nama, URL, atau host" class="input pl-9">
        </div>
        <select name="type" aria-label="Saring berdasarkan jenis monitor" class="input sm:w-44" onchange="this.form.submit()">
            <option value="">Semua jenis</option>
            @foreach (\App\Enums\MonitorType::cases() as $typeOption)
                <option value="{{ $typeOption->value }}" @selected($type === $typeOption->value)>{{ $typeOption->label() }}</option>
            @endforeach
        </select>
        <select name="server" aria-label="Saring berdasarkan server" class="input sm:w-48" onchange="this.form.submit()">
            <option value="">Semua server</option>
            @foreach ($servers as $server)
                <option value="{{ $server->id }}" @selected((string) $serverId === (string) $server->id)>{{ $server->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="btn-secondary">Filter</button>
            @if ($type || $serverId || $search !== '')
                <a href="{{ route('monitors.index') }}" class="btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <div class="card overflow-hidden">
        @if ($monitors->isEmpty())
            <div class="p-5">
                <x-empty-state
                    title="Tidak ada monitor"
                    description="Belum ada monitor yang cocok dengan filter ini. Tambahkan monitor baru atau ubah filter."
                />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <th scope="col" class="px-4 py-3 sm:px-5">Monitor</th>
                            <th scope="col" class="px-4 py-3">Server</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 text-right">Uptime 24j</th>
                            <th scope="col" class="px-4 py-3 text-right">Respons</th>
                            <th scope="col" class="px-4 py-3 text-right">Cek terakhir</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($monitors as $monitor)
                            @php
                                $pending = $monitor->status !== \App\Enums\MonitorStatus::Down && $monitor->consecutive_failures > 0;
                            @endphp
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-4 py-3.5 sm:px-5">
                                    <a href="{{ route('monitors.show', $monitor) }}" class="group flex items-start gap-3">
                                        <span class="mt-0.5 shrink-0">
                                            <x-type-badge :type="$monitor->type" />
                                        </span>
                                        <span class="min-w-0">
                                            <span class="flex items-center gap-2">
                                                <span class="truncate font-semibold text-slate-900 group-hover:text-teal-700">{{ $monitor->name }}</span>
                                                @unless ($monitor->is_active)
                                                    <span class="badge bg-slate-100 text-slate-700 ring-slate-400/30">Nonaktif</span>
                                                @endunless
                                            </span>
                                            <span class="block truncate font-mono text-[11px] text-slate-500">{{ \Illuminate\Support\Str::limit($monitor->target(), 52) }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-xs text-slate-600">
                                    {{ $monitor->server?->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <x-status-badge :status="$monitor->status" :pending="$pending" />
                                    @if ($monitor->open_incident)
                                        <p class="mt-1 text-[11px] text-rose-700">sejak {{ \App\Support\Format::relative($monitor->open_incident->started_at) }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-semibold text-slate-700">
                                    {{ \App\Support\Format::percent($monitor->uptime24['uptime'], 1) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right text-slate-600">
                                    {{ \App\Support\Format::responseTime($monitor->last_response_ms) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right text-xs text-slate-500" title="{{ \App\Support\Format::datetime($monitor->last_checked_at) }}">
                                    {{ \App\Support\Format::relative($monitor->last_checked_at) }}
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <form method="POST" action="{{ route('monitors.check', $monitor) }}" x-data="{ submitting: false }" @submit="submitting = true">
                                            @csrf
                                            <button type="submit" :disabled="submitting" aria-label="Periksa {{ $monitor->name }}" :aria-label="submitting ? 'Memeriksa {{ $monitor->name }}' : 'Periksa {{ $monitor->name }}'" class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 transition hover:bg-teal-50 hover:text-teal-700 disabled:cursor-not-allowed disabled:opacity-60" title="Periksa sekarang">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" :class="submitting && 'animate-spin'">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                            </button>
                                        </form>
                                        <a href="{{ route('monitors.edit', $monitor) }}" aria-label="Ubah {{ $monitor->name }}" class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-700" title="Ubah">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
