@props(['type'])

@php
    $value = $type instanceof \App\Enums\MonitorType ? $type : \App\Enums\MonitorType::from($type);

    $icon = match ($value) {
        \App\Enums\MonitorType::Http => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.949 8.949 0 0 0 12 21Zm0-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
        \App\Enums\MonitorType::Port => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244',
        \App\Enums\MonitorType::Service => 'M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3V6.75a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v4.5a3 3 0 0 1-3 3m-13.5 0v3.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-3.75',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600']) }}>
    <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
    </svg>
    {{ $value->label() }}
</span>
