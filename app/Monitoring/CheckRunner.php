<?php

namespace App\Monitoring;

use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Monitoring\Checkers\HttpChecker;
use App\Monitoring\Checkers\PortChecker;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CheckRunner
{
    public function __construct(
        private readonly HttpChecker $httpChecker,
        private readonly PortChecker $portChecker,
    ) {}

    public function run(Monitor $monitor): CheckOutcome
    {
        try {
            return match ($monitor->type) {
                MonitorType::Http, MonitorType::Service => $this->httpChecker->check($monitor),
                MonitorType::Port => $this->portChecker->check($monitor),
            };
        } catch (Throwable $exception) {
            Log::error('Pemeriksaan monitor gagal dijalankan', [
                'monitor_id' => $monitor->id,
                'exception' => $exception->getMessage(),
            ]);

            return CheckOutcome::down('Pemeriksaan gagal: '.$exception->getMessage());
        }
    }
}
