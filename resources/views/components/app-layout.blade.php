@props([
    'title',
    'subtitle' => null,
])

@php
    $navigation = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'pattern' => 'dashboard',
            'icon' => 'home',
        ],
        [
            'label' => 'Monitor',
            'route' => 'monitors.index',
            'pattern' => 'monitors.*',
            'icon' => 'globe',
        ],
        [
            'label' => 'Insiden',
            'route' => 'incidents.index',
            'pattern' => 'incidents.*',
            'icon' => 'warning',
        ],
        [
            'label' => 'Laporan',
            'route' => 'reports.index',
            'pattern' => 'reports.*',
            'icon' => 'chart',
        ],
        [
            'label' => 'Server',
            'route' => 'servers.index',
            'pattern' => 'servers.*',
            'icon' => 'server',
        ],
    ];

    $openIncidents = \App\Models\Incident::query()->whereNull('resolved_at')->count();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <a href="#konten" class="skip-link">Lewati ke konten</a>
    <div class="min-h-full">
        <div
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
            x-show="sidebarOpen"
            x-transition.opacity
            x-cloak
            @click="sidebarOpen = false"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-950 text-slate-300 transition-transform duration-200 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full invisible lg:visible'"
            x-cloak
        >
            <div class="flex h-16 items-center justify-between px-5">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-500/15 ring-1 ring-inset ring-teal-400/30">
                        <svg class="h-5 w-5 text-teal-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h3l2.25-6 3.75 12 2.25-6h5.25" />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-base font-extrabold tracking-tight text-white">Pantau</span>
                        <span class="block text-[11px] font-medium uppercase tracking-widest text-slate-400">Monitoring</span>
                    </span>
                </a>
                <button type="button" class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" @click="sidebarOpen = false">
                    <span class="sr-only">Tutup menu</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="mt-2 flex-1 space-y-1 overflow-y-auto px-3" aria-label="Navigasi utama">
                @foreach ($navigation as $item)
                    @php
                        $active = request()->routeIs($item['pattern']);
                    @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        @if ($active) aria-current="page" @endif
                        class="group flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-teal-500/10 text-white ring-1 ring-inset ring-teal-400/20' : 'text-slate-400 hover:bg-slate-900 hover:text-white' }}"
                    >
                        <span class="{{ $active ? 'text-teal-400' : 'text-slate-400 group-hover:text-slate-300' }}">
                            @switch($item['icon'])
                                @case('home')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.125 1.125 0 0 1 1.592 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                                    </svg>
                                    @break
                                @case('globe')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.949 8.949 0 0 0 12 21Zm0-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                    </svg>
                                    @break
                                @case('warning')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                    </svg>
                                    @break
                                @case('chart')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                    </svg>
                                    @break
                                @case('server')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3V6.75a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v4.5a3 3 0 0 1-3 3m-13.5 0v3.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-3.75M6.75 6.75h.008v.008H6.75V6.75Zm0 10.5h.008v.008H6.75v-.008Z" />
                                    </svg>
                                    @break
                            @endswitch
                        </span>
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if ($item['label'] === 'Insiden' && $openIncidents > 0)
                            <span class="rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $openIncidents }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-slate-800/80 p-3">
                <div class="rounded-xl bg-slate-900/80 p-3 ring-1 ring-inset ring-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-500/15 text-xs font-bold uppercase text-teal-300">
                            {{ str(auth()->user()?->name ?? 'A')->substr(0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-white">{{ auth()->user()?->name }}</p>
                            <p class="truncate text-[11px] text-slate-400">{{ auth()->user()?->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg bg-slate-800 px-2 py-1.5 text-xs font-semibold text-slate-300 transition hover:bg-slate-700 hover:text-white">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                            </svg>
                            Keluar
                        </button>
                    </form>
                </div>
                <p class="mt-2 px-1 text-[10px] text-slate-400">Pantau MVP · {{ config('monitoring.display_timezone') }}</p>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button type="button" class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 lg:hidden" @click="sidebarOpen = true">
                        <span class="sr-only">Buka menu</span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-lg font-bold tracking-tight text-slate-900">{{ $title }}</h1>
                        @if ($subtitle)
                            <p class="truncate text-xs text-slate-500">{{ $subtitle }}</p>
                        @endif
                    </div>

                    <div
                        class="hidden items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500 sm:flex"
                        x-data="{ now: new Date() }"
                        x-init="setInterval(() => now = new Date(), 30000)"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span x-text="now.toLocaleString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })"></span>
                        <span class="text-slate-500">WIB</span>
                    </div>

                    @isset($actions)
                        <div class="flex items-center gap-2">{{ $actions }}</div>
                    @endisset
                </div>
            </header>

            <main id="konten" tabindex="-1" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if (session('status'))
                    <div
                        class="no-print mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                        role="status"
                        x-data="{ visible: true }"
                        x-show="visible"
                        x-transition
                    >
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="flex-1">{{ session('status') }}</p>
                        <button type="button" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-emerald-700 hover:bg-emerald-100" @click="visible = false" aria-label="Tutup">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="no-print mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <ul class="list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{ $slot }}

                <footer class="no-print mt-10 flex flex-col items-center justify-between gap-2 border-t border-slate-200 pt-5 text-[11px] text-slate-600 sm:flex-row">
                    <p>Pantau · pemantauan ketersediaan website, port, dan layanan.</p>
                    <p>Data mentah disimpan {{ config('monitoring.retention_days') }} hari · rollup harian permanen</p>
                </footer>
            </main>
        </div>
    </div>
</body>
</html>
