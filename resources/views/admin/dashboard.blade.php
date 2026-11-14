<x-app-layout title="Dashboard Admin" subtitle="Ringkasan akun pengguna Pantau">
    <x-slot:actions>
        <a href="{{ route('users.create') }}" class="btn-primary">
            <span>Tambah pengguna</span>
        </a>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <x-stat-card label="Total pengguna" :value="$stats['users']" hint="Semua akun terdaftar" />
        <x-stat-card label="Admin" :value="$stats['admins']" hint="Akun dengan akses admin" tone="teal" />
    </div>

    <section class="mt-6" aria-labelledby="users-heading">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 id="users-heading" class="text-base font-bold text-slate-900">Pengguna</h2>
                <p class="mt-1 text-xs text-slate-500">Ringkasan akun dan peran pengguna.</p>
            </div>
            <a href="{{ route('users.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">Kelola akun</a>
        </div>

        <div class="card overflow-hidden">
            @if ($users->isEmpty())
                <div class="p-5">
                    <x-empty-state title="Belum ada pengguna" description="Akun pengguna akan tampil di sini setelah dibuat." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/70">
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <th scope="col" class="px-4 py-3 sm:px-5">Nama</th>
                                <th scope="col" class="px-4 py-3">Email</th>
                                <th scope="col" class="px-4 py-3">Peran</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3">Terdaftar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3.5 font-semibold text-slate-900 sm:px-5">{{ $user->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 font-mono text-xs text-slate-600">{{ $user->email }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5">
                                        @if ($user->is_admin)
                                            <span class="badge bg-teal-50 text-teal-700 ring-teal-200">Admin</span>
                                        @else
                                            <span class="badge bg-slate-100 text-slate-600 ring-slate-200">Pengguna</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-xs text-slate-500">{{ \App\Support\Format::date($user->created_at) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
