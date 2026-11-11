@props(['status', 'pending' => false])

@php
    $value = $status instanceof \App\Enums\MonitorStatus ? $status->value : $status;

    if ($pending) {
        $classes = 'bg-amber-50 text-amber-700 ring-amber-600/20';
        $dot = 'bg-amber-500';
        $label = 'Menunggu konfirmasi';
    } else {
        [$classes, $dot, $label] = match ($value) {
            'up' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500', 'Normal'],
            'down' => ['bg-rose-50 text-rose-700 ring-rose-600/20', 'bg-rose-500', 'Gangguan'],
            default => ['bg-slate-100 text-slate-600 ring-slate-500/20', 'bg-slate-400', 'Belum diperiksa'],
        };
    }
@endphp

<span {{ $attributes->merge(['class' => "badge {$classes}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
    {{ $label }}
</span>
