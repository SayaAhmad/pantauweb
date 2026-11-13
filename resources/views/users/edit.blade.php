<x-app-layout title="Ubah pengguna" subtitle="Perbarui data, kata sandi, atau peran akun">
    <x-slot:actions>
        <a href="{{ route('users.index') }}" class="btn-secondary">Kembali</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('users.update', $user) }}" class="mx-auto max-w-2xl" x-data="{ saving: false }" @submit="saving = true">
        @csrf
        @method('PUT')
        @include('users._form')
    </form>
</x-app-layout>
