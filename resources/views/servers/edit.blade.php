<x-app-layout :title="'Ubah · '.$server->name" subtitle="Perubahan nama berlaku pada filter dan laporan">
    <x-slot:actions>
        <a href="{{ route('servers.index') }}" class="btn-secondary">Kembali</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('servers.update', $server) }}" class="mx-auto max-w-2xl" x-data="{ saving: false }" @submit="saving = true">
        @csrf
        @method('PUT')
        @include('servers._form')
    </form>
</x-app-layout>
