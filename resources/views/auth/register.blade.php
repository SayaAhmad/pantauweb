<x-guest-layout title="Buat akun">
    <div class="mb-8 flex flex-col items-center text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-500/15 ring-1 ring-inset ring-teal-400/30">
            <svg class="h-7 w-7 text-teal-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h3l2.25-6 3.75 12 2.25-6h5.25" />
            </svg>
        </span>
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">Buat akun Pantau</h1>
        <p class="mt-1 text-sm text-slate-400">Daftar untuk memantau website, port, dan layanan.</p>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-7 shadow-2xl backdrop-blur">
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3.5 py-3 text-sm text-rose-200" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-slate-300">Nama</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    maxlength="120"
                    autofocus
                    autocomplete="name"
                    placeholder="Nama lengkap"
                    class="min-h-11 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-400"
                >
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-slate-300">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    maxlength="255"
                    autocomplete="email"
                    placeholder="nama@example.com"
                    class="min-h-11 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-400"
                >
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-300">Kata sandi</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    minlength="12"
                    autocomplete="new-password"
                    placeholder="Minimal 12 karakter"
                    class="min-h-11 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-400"
                >
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-300">Ulangi kata sandi</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    minlength="12"
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi"
                    class="min-h-11 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-400"
                >
            </div>

            <button type="submit" class="min-h-11 w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-teal-950/40 transition hover:bg-teal-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                Buat akun
            </button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-400">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold text-teal-400 hover:text-teal-300">Masuk</a>
        </p>
    </div>
</x-guest-layout>
