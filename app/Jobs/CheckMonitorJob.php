<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Monitoring\CheckRunner;
use App\Monitoring\StatusEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckMonitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public readonly Monitor $monitor) {}

    public function handle(CheckRunner $runner, StatusEngine $engine): void
    {
        $monitor = $this->monitor->fresh();

        if ($monitor === null || ! $monitor->is_active) {
            return;
        }

        $engine->record($monitor, $runner->run($monitor));
    }
}
