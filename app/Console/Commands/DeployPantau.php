<?php

namespace App\Console\Commands;

use Database\Seeders\AdminSeeder;
use Illuminate\Console\Command;

class DeployPantau extends Command
{
    protected $signature = 'pantau:deploy';

    protected $description = 'Jalankan migrasi dan buat akun admin untuk deployment';

    public function handle(): int
    {
        $this->reportEnvironment();

        if (! $this->hasValidEnvironment()) {
            return self::FAILURE;
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', [
            '--class' => AdminSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }

    /**
     * Cetak konfigurasi efektif tanpa membocorkan password.
     * Baris ini muncul di log job Wasmer sehingga penyebab gagal deploy
     * langsung terlihat (mis. DB_CONNECTION=pgsql karena secret lama).
     */
    private function reportEnvironment(): void
    {
        $driver = (string) config('database.default');
        $connection = config("database.connections.{$driver}", []);

        $host = $connection['host'] ?? '-';
        $database = $connection['database'] ?? '-';

        $this->line(sprintf(
            'PHP %s | APP_ENV=%s | DB=%s@%s/%s | QUEUE=%s | CACHE=%s',
            PHP_VERSION,
            config('app.env'),
            $driver,
            $host,
            $database,
            config('queue.default'),
            config('cache.default'),
        ));
    }

    private function hasValidEnvironment(): bool
    {
        $errors = [];
        $driver = (string) config('database.default');
        $connection = config("database.connections.{$driver}", []);
        $host = (string) ($connection['host'] ?? '');

        if (blank(config('app.key'))) {
            $errors[] = 'APP_KEY kosong. Buat secret APP_KEY dari `php artisan key:generate --show`.';
        }

        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            $errors[] = "DB_CONNECTION={$driver}. Wasmer Edge memakai MySQL; hapus secret DB_CONNECTION lama lalu deploy ulang.";
        }

        if ($driver === 'mysql' && ! extension_loaded('pdo_mysql')) {
            $errors[] = 'Ekstensi pdo_mysql tidak ada. Pastikan wasmer.toml memakai runtime php/php-eh.';
        }

        if ($driver === 'pgsql' && ! extension_loaded('pdo_pgsql')) {
            $errors[] = 'Ekstensi pdo_pgsql tidak ada. Pastikan wasmer.toml memakai runtime php/php-eh.';
        }

        if (in_array($host, ['127.0.0.1', 'localhost'], true)) {
            $errors[] = "DB_HOST={$host} tidak bisa dijangkau dari Wasmer Edge. Hapus secret DB_HOST agar memakai database terkelola Wasmer.";
        }

        if (config('app.env') === 'local') {
            $errors[] = 'APP_ENV=local. Hapus secret APP_ENV agar app.yaml memakai production.';
        }

        if (config('queue.default') !== 'sync') {
            $this->warn(sprintf(
                'QUEUE_CONNECTION=%s. Edge tidak punya worker persisten; app.yaml memakai sync. Hapus secret QUEUE_CONNECTION.',
                config('queue.default'),
            ));
        }

        foreach ($errors as $error) {
            $this->error($error);
        }

        if ($errors) {
            $this->newLine();
            $this->error('Deploy dibatalkan. Bersihkan secret yang menimpa app.yaml, lalu deploy ulang.');
            $this->line('Panduan: docs/DEPLOY_WASMER.md bagian "Kalau sudah terlanjur mengunggah .env".');
        }

        return $errors === [];
    }
}
