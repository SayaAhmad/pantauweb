<?php

namespace Tests\Feature;

use App\Enums\MonitorStatus;
use App\Mail\IncidentAlertMail;
use App\Models\Monitor;
use App\Monitoring\CheckOutcome;
use App\Monitoring\StatusEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MonitorStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_changes_only_after_confirmation_threshold(): void
    {
        $engine = app(StatusEngine::class);
        $monitor = Monitor::factory()->create();
        $firstFailure = now()->subMinutes(10)->startOfSecond();

        $engine->record($monitor, CheckOutcome::down('Pemeriksaan melewati batas waktu', checkedAt: $firstFailure));
        $monitor->refresh();

        $this->assertSame(MonitorStatus::Unknown, $monitor->status);
        $this->assertSame(0, $monitor->incidents()->count());
        $this->assertTrue($monitor->failure_streak_started_at->equalTo($firstFailure));

        $engine->record($monitor, CheckOutcome::down('Pemeriksaan melewati batas waktu', checkedAt: now()->subMinutes(5)));
        $monitor->refresh();

        $this->assertSame(MonitorStatus::Down, $monitor->status);

        $incident = $monitor->incidents()->first();

        $this->assertNotNull($incident);
        $this->assertTrue($incident->started_at->equalTo($firstFailure));
        $this->assertNull($incident->resolved_at);
    }

    public function test_repeated_failures_do_not_create_duplicate_incidents(): void
    {
        $engine = app(StatusEngine::class);
        $monitor = Monitor::factory()->create();

        foreach (range(1, 5) as $minute) {
            $engine->record($monitor, CheckOutcome::down('Gagal', checkedAt: now()->subMinutes(10 - $minute)));
        }

        $this->assertSame(1, $monitor->incidents()->count());
    }

    public function test_recovery_resolves_incident_at_first_successful_check(): void
    {
        $engine = app(StatusEngine::class);
        $monitor = Monitor::factory()->create();

        $firstFailure = now()->subMinutes(30)->startOfSecond();
        $secondFailure = now()->subMinutes(25)->startOfSecond();
        $firstSuccess = now()->subMinutes(20)->startOfSecond();
        $secondSuccess = now()->subMinutes(15)->startOfSecond();

        $engine->record($monitor, CheckOutcome::down('Timeout', checkedAt: $firstFailure));
        $engine->record($monitor, CheckOutcome::down('Timeout', checkedAt: $secondFailure));

        $monitor->refresh();
        $this->assertSame(MonitorStatus::Down, $monitor->status);

        $engine->record($monitor, CheckOutcome::up(httpStatus: 200, responseTimeMs: 120, checkedAt: $firstSuccess));

        $monitor->refresh();
        $this->assertSame(MonitorStatus::Down, $monitor->status);
        $this->assertNull($monitor->incidents()->first()->resolved_at);

        $engine->record($monitor, CheckOutcome::up(httpStatus: 200, responseTimeMs: 115, checkedAt: $secondSuccess));

        $monitor->refresh();
        $incident = $monitor->incidents()->first();

        $this->assertSame(MonitorStatus::Up, $monitor->status);
        $this->assertNotNull($incident->resolved_at);
        $this->assertTrue($incident->resolved_at->equalTo($firstSuccess));
        $this->assertSame((int) $firstSuccess->diffInSeconds($firstFailure, true), $incident->duration_seconds);
    }

    public function test_unknown_monitor_becomes_up_after_first_success(): void
    {
        $engine = app(StatusEngine::class);
        $monitor = Monitor::factory()->create();

        $engine->record($monitor, CheckOutcome::up(httpStatus: 200, responseTimeMs: 90, checkedAt: now()->startOfSecond()));

        $monitor->refresh();

        $this->assertSame(MonitorStatus::Up, $monitor->status);
        $this->assertSame(0, $monitor->incidents()->count());
    }

    public function test_incident_email_is_sent_when_configured(): void
    {
        Mail::fake();
        config(['monitoring.alert_email' => 'ops@example.test']);

        $engine = app(StatusEngine::class);
        $monitor = Monitor::factory()->create();

        $engine->record($monitor, CheckOutcome::down('Timeout', checkedAt: now()->subMinutes(2)));
        $engine->record($monitor, CheckOutcome::down('Timeout', checkedAt: now()->subMinute()));

        Mail::assertSent(IncidentAlertMail::class);
    }
}
