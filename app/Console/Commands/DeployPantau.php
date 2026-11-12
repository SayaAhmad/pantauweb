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
        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', [
            '--class' => AdminSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
