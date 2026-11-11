<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonitorJob;
use App\Models\Monitor;
use Illuminate\Console\Command;

class DispatchMonitorChecks extends Command
{
    protected $signature = 'monitors:dispatch';

    protected $description = 'Jadwalkan pemeriksaan untuk monitor yang sudah jatuh tempo';

    public function handle(): int
    {
        $due = Monitor::query()->active()->due()->get();

        foreach ($due as $monitor) {
            $monitor->forceFill([
                'next_check_at' => now()->addSeconds(max(60, $monitor->interval_seconds)),
            ])->save();

            CheckMonitorJob::dispatch($monitor);
        }

        $this->info("{$due->count()} pemeriksaan dijadwalkan.");

        return self::SUCCESS;
    }
}
