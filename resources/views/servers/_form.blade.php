<div class="space-y-4">
    <div class="card p-5 sm:p-6">
        <div class="space-y-4">
            <div>
                <label for="name" class="label">Nama server</label>
                <input id="name" name="name" type="text" value="{{ old('name', $server->name) }}" required maxlength="120" class="input" placeholder="Contoh: Server Produksi">
                <x-input-error for="name" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="hostname" class="label">Hostname</label>
                    <input id="hostname" name="hostname" type="text" value="{{ old('hostname', $server->hostname) }}" required maxlength="255" class="input font-mono text-xs sm:text-sm" placeholder="prod-01.internal">
                    <x-input-error for="hostname" />
                </div>
                <div>
                    <label for="ip" class="label">Alamat IP <span class="font-normal text-slate-500">(opsional)</span></label>
                    <input id="ip" name="ip" type="text" value="{{ old('ip', $server->ip) }}" class="input font-mono text-xs sm:text-sm" placeholder="10.0.0.12">
                    <x-input-error for="ip" />
                </div>
            </div>

            <div>
                <label for="description" class="label">Deskripsi <span class="font-normal text-slate-500">(opsional)</span></label>
                <textarea id="description" name="description" rows="3" class="input" placeholder="Peran server, lokasi, atau penanggung jawab.">{{ old('description', $server->description) }}</textarea>
                <x-input-error for="description" />
            </div>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a href="{{ route('servers.index') }}" class="btn-secondary">Batal</a>
        <button type="submit" :disabled="saving" class="btn-primary"><span x-text="saving ? 'Menyimpan…' : '{{ $server->exists ? 'Simpan perubahan' : 'Tambah server' }}'">{{ $server->exists ? 'Simpan perubahan' : 'Tambah server' }}</span></button>
    </div>
</div>
