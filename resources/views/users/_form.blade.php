<div class="space-y-4">
    <div class="card p-5 sm:p-6">
        <div class="space-y-4">
            <div>
                <label for="name" class="label">Nama</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name" class="input" placeholder="Nama lengkap" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                <x-input-error for="name" />
            </div>

            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email" class="input" placeholder="nama@example.com" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                <x-input-error for="email" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="label">
                        Kata sandi
                        @if ($user->exists)
                            <span class="font-normal text-slate-500">(opsional)</span>
                        @endif
                    </label>
                    <input id="password" name="password" type="password" @required(! $user->exists) minlength="12" autocomplete="new-password" class="input" placeholder="Minimal 12 karakter" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    <x-input-error for="password" />
                </div>
                <div>
                    <label for="password_confirmation" class="label">Ulangi kata sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" @required(! $user->exists) minlength="12" autocomplete="new-password" class="input" placeholder="Ulangi kata sandi">
                </div>
            </div>

            @if ($user->exists)
                <p class="hint">Kosongkan kedua kolom kata sandi jika tidak ingin mengubahnya.</p>
            @endif

            <div>
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50/70 p-3">
                    <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user->is_admin)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600" @error('is_admin') aria-invalid="true" aria-describedby="is_admin-error" @enderror>
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Akses admin</span>
                        <span class="block text-xs text-slate-500">Admin bisa menambah, mengubah, dan menghapus akun lain.</span>
                    </span>
                </label>
                <x-input-error for="is_admin" />
                @if ($user->is(auth()->user()))
                    <p class="hint">Anda tidak bisa mencabut akses admin dari akun sendiri.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a href="{{ route('users.index') }}" class="btn-secondary">Batal</a>
        <button type="submit" :disabled="saving" class="btn-primary"><span x-text="saving ? 'Menyimpan...' : '{{ $user->exists ? 'Simpan perubahan' : 'Tambah pengguna' }}'">{{ $user->exists ? 'Simpan perubahan' : 'Tambah pengguna' }}</span></button>
    </div>
</div>
