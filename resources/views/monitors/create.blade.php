<x-app-layout title="Tambah monitor" subtitle="Monitor baru akan diperiksa pada siklus berikutnya">
    <x-slot:actions>
        <a href="{{ route('monitors.index') }}" class="btn-secondary">Kembali</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('monitors.store') }}" class="mx-auto max-w-3xl">
        @csrf
        @include('monitors._form')
    </form>
</x-app-layout>
