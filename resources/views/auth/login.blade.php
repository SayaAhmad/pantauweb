<x-guest-layout title="Masuk">
    <div class="mb-8 flex flex-col items-center text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-500/15 ring-1 ring-inset ring-teal-400/30">
            <svg class="h-7 w-7 text-teal-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h3l2.25-6 3.75 12 2.25-6h5.25" />
            </svg>
        </span>
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">Pantau</h1>
        <p class="mt-1 text-sm text-slate-400">Masuk untuk memantau website, port, dan layanan.</p>
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

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-slate-300">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="admin@pantau.test"
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
                    autocomplete="current-password"
                    placeholder="Kata sandi"
                    class="min-h-11 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-400"
                >
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-teal-500 focus:ring-teal-400">
                Ingat saya
            </label>

            <button type="submit" class="min-h-11 w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-teal-950/40 transition hover:bg-teal-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                Masuk
            </button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-400">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-teal-400 hover:text-teal-300">Buat akun</a>
        </p>

        @if (app()->environment('local'))
            <div class="mt-6 rounded-lg border border-slate-800 bg-slate-950/50 px-3.5 py-3 text-xs text-slate-400">
                Akun demo hasil seeder:
                <span class="font-semibold text-slate-300">admin@pantau.test</span> /
                <span class="font-semibold text-slate-300">password</span>
            </div>
        @endif
    </div>

    <p class="mt-6 text-center text-[11px] text-slate-400">Satu instalasi untuk satu organisasi · MVP</p>
</x-guest-layout>
