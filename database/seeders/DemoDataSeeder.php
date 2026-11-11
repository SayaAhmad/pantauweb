<?php

namespace Database\Seeders;

use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * @var array<string, array{base: int, ssl_days: int|null, issuer: string|null}>
     */
    private array $profiles = [
        'Website Utama' => ['base' => 145, 'ssl_days' => 74, 'issuer' => "Let's Encrypt R11"],
        'API Gateway' => ['base' => 260, 'ssl_days' => 21, 'issuer' => 'Google Trust Services WE1'],
        'SSH Produksi' => ['base' => 24, 'ssl_days' => null, 'issuer' => null],
        'Database PostgreSQL' => ['base' => 38, 'ssl_days' => null, 'issuer' => null],
        'Website Marketing' => ['base' => 96, 'ssl_days' => 6, 'issuer' => 'DigiCert TLS RSA SHA256 2020 CA1'],
    ];

    public function run(): void
    {
        $monitors = Monitor::query()->orderBy('id')->get();

        if ($monitors->isEmpty()) {
            $this->command?->warn('Tidak ada monitor. Jalankan DatabaseSeeder terlebih dahulu.');

            return;
        }

        $now = now()->startOfMinute();

        $outageWindows = [
            'Website Marketing' => [
                [$now->copy()->subHours(52), $now->copy()->subHours(51)->subMinutes(40)],
            ],
            'API Gateway' => [
                [$now->copy()->subHours(26), $now->copy()->subHours(24)->subMinutes(20)],
            ],
            'Database PostgreSQL' => [
                [$now->copy()->subMinutes(45), null],
            ],
        ];

        CheckResult::query()->delete();
        Incident::query()->delete();

        foreach ($monitors as $monitor) {
            $window = $outageWindows[$monitor->name] ?? [];
            $profile = $this->profiles[$monitor->name] ?? ['base' => 120, 'ssl_days' => 45, 'issuer' => "Let's Encrypt R11"];

            $rows = [];
            $index = 0;
            $lastDown = false;
            $lastCheckedAt = $now;
            $lastResponseMs = null;

            for ($time = $now->copy()->subHours(72); $time->lessThanOrEqualTo($now); $time->addMinutes(5)) {
                $isDown = $this->isDown($window, $time);
                $responseMs = $isDown
                    ? null
                    : (int) max(8, round($profile['base'] + (sin($index / 5) * 18) + (cos($index / 11) * 9)));

                $rows[] = [
                    'monitor_id' => $monitor->id,
                    'status' => $isDown ? 'down' : 'up',
                    'http_status' => $isDown ? null : ($monitor->type->value === 'port' ? null : 200),
                    'response_time_ms' => $responseMs,
                    'error_message' => $isDown ? $this->errorFor($monitor) : null,
                    'ssl_expires_at' => $profile['ssl_days'] !== null
                        ? $now->copy()->addDays($profile['ssl_days'])->format('Y-m-d H:i:s')
                        : null,
                    'checked_at' => $time->format('Y-m-d H:i:s'),
                    'created_at' => $time->format('Y-m-d H:i:s'),
                    'updated_at' => $time->format('Y-m-d H:i:s'),
                ];

                $lastDown = $isDown;
                $lastCheckedAt = $time->copy();
                $lastResponseMs = $responseMs;
                $index++;
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('check_results')->insert($chunk);
            }

            $openWindow = collect($window)->first(fn ($item) => $item[1] === null);

            $monitor->forceFill([
                'status' => $lastDown ? 'down' : 'up',
                'consecutive_failures' => $lastDown ? $this->countTrailing($rows, 'down') : 0,
                'consecutive_successes' => $lastDown ? 0 : $this->countTrailing($rows, 'up'),
                'failure_streak_started_at' => $openWindow[0] ?? null,
                'recovery_streak_started_at' => null,
                'last_checked_at' => $lastCheckedAt,
                'last_response_ms' => $lastResponseMs,
                'last_http_status' => $lastDown || $monitor->type->value === 'port' ? null : 200,
                'last_error' => $lastDown ? $this->errorFor($monitor) : null,
                'next_check_at' => now()->addMinutes(2),
                'ssl_expires_at' => $profile['ssl_days'] !== null
                    ? $now->copy()->addDays($profile['ssl_days'])
                    : null,
                'ssl_issuer' => $profile['issuer'],
            ])->save();

            foreach ($window as [$start, $end]) {
                Incident::query()->create([
                    'monitor_id' => $monitor->id,
                    'started_at' => $start,
                    'resolved_at' => $end,
                    'duration_seconds' => $end === null ? null : (int) $start->diffInSeconds($end, true),
                    'cause' => $this->errorFor($monitor),
                ]);
            }
        }

        Artisan::call('monitors:rollup', ['--days' => 4]);

        $this->command?->info('Data demo 72 jam dibuat untuk '.$monitors->count().' monitor.');
    }

    /**
     * @param  array<int, array{0: Carbon, 1: Carbon|null}>  $windows
     */
    private function isDown(array $windows, Carbon $time): bool
    {
        foreach ($windows as [$start, $end]) {
            if ($time->greaterThanOrEqualTo($start) && ($end === null || $time->lessThanOrEqualTo($end))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function countTrailing(array $rows, string $status): int
    {
        $count = 0;

        foreach (array_reverse($rows) as $row) {
            if ($row['status'] !== $status) {
                break;
            }

            $count++;
        }

        return $count;
    }

    private function errorFor(Monitor $monitor): string
    {
        return $monitor->type->value === 'port'
            ? "Port {$monitor->port} tidak dapat dijangkau: Connection refused"
            : 'Pemeriksaan melewati batas waktu';
    }
}
