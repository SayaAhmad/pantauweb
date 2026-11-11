<x-app-layout :title="'Ubah · '.$monitor->name" subtitle="Perubahan berlaku pada pemeriksaan berikutnya">
    <x-slot:actions>
        <a href="{{ route('monitors.show', $monitor) }}" class="btn-secondary">Batal</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('monitors.update', $monitor) }}" class="mx-auto max-w-3xl" x-data="{ saving: false }" @submit="saving = true">
        @csrf
        @method('PUT')
        @include('monitors._form')
    </form>
</x-app-layout>
