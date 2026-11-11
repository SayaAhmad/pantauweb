<x-mail::message>
# {{ $resolved ? 'Layanan pulih' : 'Gangguan terdeteksi' }}

**{{ $monitor->name }}** ({{ $monitor->type->label() }})
Target: `{{ $monitor->target() }}`

@if ($resolved)
Monitor kembali normal pada **{{ \App\Support\Format::datetime($incident->resolved_at) }}**.

Insiden berlangsung **{{ \App\Support\Format::duration($incident->duration_seconds) }}**, dimulai {{ \App\Support\Format::datetime($incident->started_at) }}.
@else
Monitor terdeteksi mengalami gangguan sejak **{{ \App\Support\Format::datetime($incident->started_at) }}**.

@if ($incident->cause)
Penyebab: {{ $incident->cause }}
@endif
@endif

<x-mail::button :url="route('monitors.show', $monitor)">
Lihat detail monitor
</x-mail::button>

Salam,
Pantau
</x-mail::message>
