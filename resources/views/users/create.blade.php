<x-app-layout title="Tambah pengguna" subtitle="Buat akun baru untuk anggota tim">
    <x-slot:actions>
        <a href="{{ route('users.index') }}" class="btn-secondary">Kembali</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('users.store') }}" class="mx-auto max-w-2xl" x-data="{ saving: false }" @submit="saving = true">
        @csrf
        @include('users._form')
    </form>
</x-app-layout>
