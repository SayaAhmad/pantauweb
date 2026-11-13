<x-app-layout title="Pengguna" subtitle="Akun yang bisa mengakses Pantau">
    <x-slot:actions>
        <a href="{{ route('users.create') }}" class="btn-primary" aria-label="Tambah pengguna">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="hidden sm:inline">Tambah pengguna</span>
        </a>
    </x-slot:actions>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs leading-relaxed text-slate-500">
        Registrasi publik aktif: siapa pun yang mendaftar lewat halaman Daftar langsung menjadi pengguna biasa. Admin tetap bisa membuat akun di halaman ini, mengubah peran, dan menghapus akun. Pengguna biasa tidak bisa mengelola akun.
    </div>

    <div class="card overflow-hidden">
        @if ($users->isEmpty())
            <div class="p-5">
                <x-empty-state title="Belum ada pengguna" description="Tambahkan akun agar tim bisa masuk ke Pantau." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <th scope="col" class="px-4 py-3 sm:px-5">Nama</th>
                            <th scope="col" class="px-4 py-3">Email</th>
                            <th scope="col" class="px-4 py-3">Peran</th>
                            <th scope="col" class="px-4 py-3">Dibuat</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $user)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-4 py-3.5 sm:px-5">
                                    <p class="font-semibold text-slate-900">
                                        {{ $user->name }}
                                        @if ($user->is(auth()->user()))
                                            <span class="ml-1 text-[11px] font-medium text-slate-500">(Anda)</span>
                                        @endif
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 font-mono text-xs text-slate-600">{{ $user->email }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    @if ($user->is_admin)
                                        <span class="badge bg-teal-50 text-teal-700 ring-teal-200">Admin</span>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-600 ring-slate-200">Pengguna</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-xs text-slate-500">{{ \App\Support\Format::date($user->created_at) }}</td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('users.edit', $user) }}" aria-label="Ubah {{ $user->name }}" class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-700" title="Ubah">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                        @if (! $user->is(auth()->user()))
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Hapus akun ini? Pengguna akan langsung kehilangan akses.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" aria-label="Hapus {{ $user->name }}" class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 transition hover:bg-rose-50 hover:text-rose-700" title="Hapus">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
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
