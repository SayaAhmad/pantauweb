<x-app-layout title="Tambah server" subtitle="Server menjadi pengelompokan monitor">
    <x-slot:actions>
        <a href="{{ route('servers.index') }}" class="btn-secondary">Kembali</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('servers.store') }}" class="mx-auto max-w-2xl">
        @csrf
        @include('servers._form')
    </form>
</x-app-layout>
