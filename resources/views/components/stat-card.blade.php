@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'slate',
])

@php
    $tones = [
        'slate' => ['text-slate-900', 'bg-slate-100', 'text-slate-700'],
        'teal' => ['text-teal-700', 'bg-teal-50', 'text-teal-700'],
        'emerald' => ['text-emerald-700', 'bg-emerald-50', 'text-emerald-700'],
        'rose' => ['text-rose-700', 'bg-rose-50', 'text-rose-700'],
        'amber' => ['text-amber-700', 'bg-amber-50', 'text-amber-700'],
    ];

    [$valueColor, $chipBackground, $chipText] = $tones[$tone] ?? $tones['slate'];
@endphp

<div {{ $attributes->merge(['class' => 'card p-4 sm:p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
        @if ($hint)
            <span class="rounded-md {{ $chipBackground }} px-1.5 py-0.5 text-[10px] font-semibold {{ $chipText }}">{{ $hint }}</span>
        @endif
    </div>
    <p class="mt-2 text-2xl font-extrabold tracking-tight {{ $valueColor }} sm:text-[1.7rem]">{{ $value }}</p>
</div>
