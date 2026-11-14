<x-guest-layout title="Masuk">
    <!-- Clean login form - no background animations -->
    <div class="relative z-10">
    <div class="mb-6 flex flex-col items-center text-center">
        <!-- Live status indicator badge -->
        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-500/30 bg-teal-950/60 px-3.5 py-1 text-xs font-medium text-teal-300 shadow-sm backdrop-blur-md">
            <span class="relative flex h-2 w-2">
                <span class="relative inline-flex h-2 w-2 rounded-full bg-teal-400"></span>
            </span>
            Sistem Pemantauan Online
        </div>

        <!-- Clean logo without chaotic wobble -->
        <div class="relative flex items-center justify-center">
            <span class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 border border-teal-500/40 shadow-xl shadow-teal-950/50">
                <svg class="h-7 w-7 text-teal-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h3l2.25-6 3.75 12 2.25-6h5.25" />
                </svg>
            </span>
        </div>
        <h1 class="mt-4 text-2xl font-bold tracking-tight text-white">
            Pantau
        </h1>
        <p class="mt-1 text-sm text-slate-400">Masuk untuk memantau website, port, dan layanan.</p>
    </div>

    <!-- Gradient border wrapper for glassmorphism card -->
    <div class="group relative rounded-2xl p-[1px] bg-gradient-to-b from-teal-400/40 via-emerald-500/20 to-slate-800 shadow-[0_0_40px_rgba(13,148,136,0.15)]">
        <div class="rounded-2xl bg-slate-900/90 p-7 backdrop-blur-2xl transition duration-300">
            @if ($errors->any())
                <div id="form-errors" class="mb-5 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200 backdrop-blur-sm" role="alert">
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
                    <div class="relative">
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="admin@pantau.test"
                            @error('email') aria-invalid="true" aria-describedby="form-errors" @enderror
                            class="min-h-11 w-full rounded-xl border border-slate-700/80 bg-slate-950/70 px-3.5 py-2.5 text-base text-white outline-none transition-all duration-200 placeholder:text-slate-500 focus:border-teal-400 focus:bg-slate-950 focus:ring-2 focus:ring-teal-400/40 focus:shadow-[0_0_20px_rgba(45,212,191,0.2)] sm:text-sm"
                        >
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-slate-300">Kata sandi</label>
                    </div>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Kata sandi"
                            @error('password') aria-invalid="true" aria-describedby="form-errors" @enderror
                            class="min-h-11 w-full rounded-xl border border-slate-700/80 bg-slate-950/70 px-3.5 py-2.5 text-base text-white outline-none transition-all duration-200 placeholder:text-slate-500 focus:border-teal-400 focus:bg-slate-950 focus:ring-2 focus:ring-teal-400/40 focus:shadow-[0_0_20px_rgba(45,212,191,0.2)] sm:text-sm"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm py-1">
                    <label class="flex items-center gap-2 text-slate-400 cursor-pointer select-none group/remember">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-teal-500 focus:ring-teal-400/50">
                        <span class="group-hover/remember:text-slate-300 transition-colors">Ingat saya</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="group relative min-h-11 w-full overflow-hidden rounded-xl bg-gradient-to-r from-teal-500 via-emerald-400 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-sm transition-all duration-300 hover:shadow-[0_0_35px_rgba(20,184,166,0.55)] hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
                >
                    <span class="relative z-10 flex items-center justify-center gap-2 font-bold tracking-wide">
                        Masuk
                        <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </button>

                <div class="relative my-4">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-slate-700/80"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-slate-900 px-2 text-slate-400">Atau masuk dengan</span>
                    </div>
                </div>

                <a
                    href="{{ route('auth.google') }}"
                    class="flex min-h-11 w-full items-center justify-center gap-3 rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-2.5 text-sm font-semibold text-slate-200 shadow-sm transition duration-200 hover:border-slate-600 hover:bg-slate-800/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17Z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24Z"/>
                        <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.99 0 12s.45 3.82 1.25 5.42l4.03-3.15Z"/>
                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98Z"/>
                    </svg>
                    <span>Masuk dengan Google</span>
                </a>
            </form>

            <p class="mt-5 text-center text-sm text-slate-400">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-semibold text-teal-400 transition-colors duration-150 hover:text-teal-300 underline underline-offset-4 decoration-teal-500/30 hover:decoration-teal-400">Buat akun</a>
            </p>

            @if (app()->environment('local'))
                <div class="mt-6 rounded-xl border border-teal-500/20 bg-gradient-to-r from-slate-950/90 to-teal-950/30 p-3.5 text-xs text-slate-400 backdrop-blur-sm">
                    <div class="flex items-center gap-1.5 font-medium text-teal-400 mb-1">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Akun demo cepat:
                    </div>
                    <div class="font-mono text-slate-300 select-all">
                        admin@pantau.test <span class="text-slate-500">/</span> password
                    </div>
                </div>
            @endif
        </div>
    </div>

    <p class="mt-6 text-center text-[11px] text-slate-500 tracking-wider uppercase font-medium">Satu instalasi untuk satu organisasi · MVP</p>
    </div>
</x-guest-layout>
