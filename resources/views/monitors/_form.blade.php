@php
    $typeValue = old('type', $monitor->type?->value ?? 'http');
    $isActiveChecked = (string) old('is_active', $monitor->exists ? (int) $monitor->is_active : 1) === '1';
@endphp

<div x-data="{ type: '{{ $typeValue }}' }" class="space-y-4">
    <div class="card p-5 sm:p-6">
        <div class="mb-5 flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 ring-1 ring-inset ring-teal-600/10">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.949 8.949 0 0 0 12 21Zm0-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                </svg>
            </span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Identitas & target</h2>
                <p class="text-xs text-slate-500">Tentukan apa yang dipantau dan di mana.</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label for="name" class="label">Nama monitor</label>
                <input id="name" name="name" type="text" value="{{ old('name', $monitor->name) }}" required maxlength="120" class="input" placeholder="Contoh: Website utama">
                <x-input-error for="name" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="type" class="label">Jenis monitor</label>
                    <select id="type" name="type" x-model="type" class="input">
                        @foreach (\App\Enums\MonitorType::cases() as $typeOption)
                            <option value="{{ $typeOption->value }}" @selected($typeValue === $typeOption->value)>{{ $typeOption->label() }}</option>
                        @endforeach
                    </select>
                    <p class="hint" x-show="type === 'http'">Website diperiksa lewat HTTP/HTTPS, termasuk masa berlaku SSL.</p>
                    <p class="hint" x-show="type === 'port'" x-cloak>Membuka koneksi TCP ke host dan port tertentu.</p>
                    <p class="hint" x-show="type === 'service'" x-cloak>Endpoint kesehatan layanan (HTTP) dengan ekspektasi status/kata kunci.</p>
                    <x-input-error for="type" />
                </div>

                <div>
                    <label for="server_id" class="label">Server <span class="font-normal text-slate-400">(opsional)</span></label>
                    <select id="server_id" name="server_id" class="input">
                        <option value="">Tanpa server</option>
                        @foreach ($servers as $server)
                            <option value="{{ $server->id }}" @selected((string) old('server_id', $monitor->server_id) === (string) $server->id)>{{ $server->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="server_id" />
                </div>
            </div>

            <div x-show="type !== 'port'" x-cloak class="space-y-4">
                <div>
                    <label for="url" class="label">URL</label>
                    <input id="url" name="url" type="url" value="{{ old('url', $monitor->url) }}" class="input font-mono text-xs sm:text-sm" placeholder="https://example.com/health">
                    <p class="hint">Harus diawali http:// atau https://.</p>
                    <x-input-error for="url" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="method" class="label">Metode HTTP</label>
                        <select id="method" name="method" class="input">
                            @foreach (['GET', 'HEAD'] as $method)
                                <option value="{{ $method }}" @selected(old('method', $monitor->method ?: 'GET') === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="method" />
                    </div>
                    <div>
                        <label for="expected_status" class="label">Kode status diharapkan</label>
                        <input id="expected_status" name="expected_status" type="number" min="100" max="599" value="{{ old('expected_status', $monitor->expected_status) }}" class="input" placeholder="Kosong = 200–399">
                        <x-input-error for="expected_status" />
                    </div>
                </div>

                <div>
                    <label for="expected_keyword" class="label">Kata kunci pada respons <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="expected_keyword" name="expected_keyword" type="text" value="{{ old('expected_keyword', $monitor->expected_keyword) }}" class="input" placeholder="Contoh: OK atau &quot;status&quot;:&quot;healthy&quot;">
                    <p class="hint">Pemeriksaan dinyatakan gagal bila kata kunci tidak ditemukan. Tidak berlaku untuk metode HEAD.</p>
                    <x-input-error for="expected_keyword" />
                </div>
            </div>

            <div x-show="type === 'port'" x-cloak class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="host" class="label">Host</label>
                    <input id="host" name="host" type="text" value="{{ old('host', $monitor->host) }}" class="input font-mono text-xs sm:text-sm" placeholder="127.0.0.1 atau db.internal">
                    <x-input-error for="host" />
                </div>
                <div>
                    <label for="port" class="label">Port</label>
                    <input id="port" name="port" type="number" min="1" max="65535" value="{{ old('port', $monitor->port) }}" class="input" placeholder="22, 80, 443, 5432">
                    <x-input-error for="port" />
                </div>
            </div>

            <div>
                <label for="notes" class="label">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                <textarea id="notes" name="notes" rows="2" class="input" placeholder="Konteks tambahan, penanggung jawab, atau prosedur saat gangguan.">{{ old('notes', $monitor->notes) }}</textarea>
                <x-input-error for="notes" />
            </div>
        </div>
    </div>

    <div class="card p-5 sm:p-6">
        <div class="mb-5 flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-400/10">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Penjadwalan & ambang</h2>
                <p class="text-xs text-slate-500">Seberapa sering diperiksa dan kapan status dianggap berubah.</p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="interval_seconds" class="label">Interval pemeriksaan</label>
                    <div class="relative">
                        <input id="interval_seconds" name="interval_seconds" type="number" min="60" max="86400" step="60" value="{{ old('interval_seconds', $monitor->interval_seconds ?? 300) }}" required class="input pr-14">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-slate-400">detik</span>
                    </div>
                    <p class="hint">Minimal 60 detik. Contoh: 300 = tiap 5 menit.</p>
                    <x-input-error for="interval_seconds" />
                </div>

                <div>
                    <label for="timeout_seconds" class="label">Batas waktu pemeriksaan</label>
                    <div class="relative">
                        <input id="timeout_seconds" name="timeout_seconds" type="number" min="1" max="60" value="{{ old('timeout_seconds', $monitor->timeout_seconds ?? 10) }}" required class="input pr-14">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-slate-400">detik</span>
                    </div>
                    <p class="hint">Maksimal 60 detik.</p>
                    <x-input-error for="timeout_seconds" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="confirmation_threshold" class="label">Ambang konfirmasi gangguan</label>
                    <input id="confirmation_threshold" name="confirmation_threshold" type="number" min="1" max="10" value="{{ old('confirmation_threshold', $monitor->confirmation_threshold ?? 2) }}" required class="input">
                    <p class="hint">Jumlah kegagalan berturut-turut sebelum monitor dinyatakan gangguan.</p>
                    <x-input-error for="confirmation_threshold" />
                </div>

                <div>
                    <label for="recovery_threshold" class="label">Ambang pemulihan</label>
                    <input id="recovery_threshold" name="recovery_threshold" type="number" min="1" max="10" value="{{ old('recovery_threshold', $monitor->recovery_threshold ?? 2) }}" required class="input">
                    <p class="hint">Jumlah keberhasilan berturut-turut sebelum monitor dinyatakan normal kembali.</p>
                    <x-input-error for="recovery_threshold" />
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input type="hidden" name="is_active" value="0">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30" @checked($isActiveChecked)>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Monitor aktif</span>
                        <span class="block text-xs text-slate-500">Monitor nonaktif berhenti dijadwalkan tetapi riwayatnya tetap tersimpan.</span>
                    </span>
                </label>
                <x-input-error for="is_active" />
            </div>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a href="{{ $monitor->exists ? route('monitors.show', $monitor) : route('monitors.index') }}" class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ $monitor->exists ? 'Simpan perubahan' : 'Buat monitor' }}
        </button>
    </div>
</div>
