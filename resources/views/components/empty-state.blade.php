@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-6 py-10 text-center']) }}>
    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-inset ring-slate-200">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h3l2.25-6 3.75 12 2.25-6h5.25" />
        </svg>
    </span>
    <p class="mt-3 text-sm font-semibold text-slate-700">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-xs text-slate-500">{{ $description }}</p>
    @endif
</div>
