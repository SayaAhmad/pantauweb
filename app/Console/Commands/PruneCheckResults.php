<?php

namespace App\Console\Commands;

use App\Models\CheckResult;
use Illuminate\Console\Command;

class PruneCheckResults extends Command
{
    protected $signature = 'monitors:prune {--days= : Retensi dalam hari (default dari konfigurasi)}';

    protected $description = 'Hapus hasil pemeriksaan mentah yang melewati masa retensi';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('monitoring.retention_days', 30));
        $cutoff = now()->subDays(max(1, $days));

        $deleted = CheckResult::query()->where('checked_at', '<', $cutoff)->delete();

        $this->info("{$deleted} hasil pemeriksaan lebih tua dari {$cutoff->toDateString()} dihapus.");

        return self::SUCCESS;
    }
}
