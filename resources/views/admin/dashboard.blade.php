<x-app-layout title="Pemantauan Pengguna" subtitle="Statistik keseluruhan semua akun pengguna">
    <x-slot:actions>
        <a href="{{ route('users.create') }}" class="btn-primary" aria-label="Tambah pengguna">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="hidden sm:inline">Tambah pengguna</span>
        </a>
    </x-slot:actions>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900">Pemantauan Pengguna</h1>
        <p class="text-xs text-slate-600">Statistik keseluruhan semua akun pengguna.</p>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-6">
        <x-stat-card
            label="Pengguna"
            :value="$stats['users']"
            tone="teal"
        />
        <x-stat-card
            label="Admin"
            :value="$stats['admins']"
            tone="emerald"
        />
        <x-stat-card
            label="Total Monitor"
            :value="$stats['monitors']"
            :hint="$stats['down'].' gangguan'"
            :tone="$stats['down'] > 0 ? 'rose' : 'slate'"
        />
        <x-stat-card
            label="Gangguan"
            :value="$stats['down']"
            tone="rose"
        />
        <x-stat-card
            label="Insiden Terbuka"
            :value="$stats['open_incidents']"
            :tone="$stats['open_incidents'] > 0 ? 'rose' : 'emerald'"
        />
        <x-stat-card
            label="Tanpa Monitor"
            :value="$stats['without_monitors']"
            :tone="$stats['without_monitors'] > 0 ? 'rose' : 'emerald'"
        />
    </div>

    <div class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5">
            <h2 class="text-sm font-bold text-slate-900">Pengguna</h2>
            <a href="{{ route('users.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">Lihat semua</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 sm:px-5">Pengguna</th>
                        <th scope="col" class="px-4 py-3">Monitor</th>
                        <th scope="col" class="px-4 py-3">Gangguan</th>
                        <th scope="col" class="px-4 py-3">Terakhir</th>
                        <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $user)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-4 py-3.5 sm:px-5">
                                <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                <p class="block text-xs text-slate-500">{{ $user->email }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-sm font-semibold text-slate-700">
                                {{ $user->monitors_count }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5">
                                @if ($user->down_monitors_count > 0)
                                    <span class="badge bg-rose-50 text-rose-700 ring-rose-200">{{ $user->down_monitors_count }} gangguan</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-600 ring-slate-200">Semua normal</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-xs text-slate-500">
                                {{ $user->last_monitors_max_checked_at ? \App\Support\Format::relative($user->last_monitors_max_checked_at) : '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('users.edit', $user) }}" aria-label="Ubah {{ $user->name }}" class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-700" title="Ubah">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>