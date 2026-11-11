<?php

namespace App\Monitoring;

use App\Enums\CheckStatus;
use App\Enums\MonitorStatus;
use App\Mail\IncidentAlertMail;
use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class StatusEngine
{
    public function record(Monitor $monitor, CheckOutcome $outcome): CheckResult
    {
        $checkedAt = $outcome->checkedAt ?? now();

        $result = $monitor->checkResults()->create([
            'status' => $outcome->up ? CheckStatus::Up : CheckStatus::Down,
            'http_status' => $outcome->httpStatus,
            'response_time_ms' => $outcome->responseTimeMs,
            'error_message' => $outcome->error,
            'ssl_expires_at' => $outcome->sslExpiresAt,
            'checked_at' => $checkedAt,
        ]);

        $previousStatus = $monitor->status;
        $openedIncident = null;
        $resolvedIncident = null;

        if ($outcome->up) {
            $monitor->consecutive_successes++;
            $monitor->consecutive_failures = 0;
            $monitor->failure_streak_started_at = null;

            if ($previousStatus === MonitorStatus::Down) {
                $monitor->recovery_streak_started_at ??= $checkedAt;
            } else {
                $monitor->recovery_streak_started_at = null;
            }

            $requiredSuccesses = $previousStatus === MonitorStatus::Down
                ? max(1, $monitor->recovery_threshold)
                : 1;

            if ($previousStatus !== MonitorStatus::Up
                && $monitor->consecutive_successes >= $requiredSuccesses) {
                $monitor->status = MonitorStatus::Up;

                if ($previousStatus === MonitorStatus::Down) {
                    $resolvedIncident = $this->resolveOpenIncident($monitor, $monitor->recovery_streak_started_at);
                }

                $monitor->recovery_streak_started_at = null;
            }
        } else {
            $monitor->consecutive_failures++;
            $monitor->consecutive_successes = 0;
            $monitor->recovery_streak_started_at = null;
            $monitor->failure_streak_started_at ??= $checkedAt;

            if ($previousStatus !== MonitorStatus::Down
                && $monitor->consecutive_failures >= $monitor->confirmation_threshold) {
                $monitor->status = MonitorStatus::Down;
                $openedIncident = $this->openIncident(
                    $monitor,
                    $monitor->failure_streak_started_at,
                    $outcome->error,
                );
            }
        }

        $monitor->last_checked_at = $checkedAt;
        $monitor->last_response_ms = $outcome->responseTimeMs;
        $monitor->last_http_status = $outcome->httpStatus;
        $monitor->last_error = $outcome->error;
        $monitor->ssl_expires_at = $outcome->sslExpiresAt ?? $monitor->ssl_expires_at;
        $monitor->ssl_issuer = $outcome->sslIssuer ?? $monitor->ssl_issuer;
        $monitor->next_check_at = now()->addSeconds(max(60, $monitor->interval_seconds));
        $monitor->save();

        if ($openedIncident !== null) {
            $this->notify($monitor, $openedIncident, resolved: false);
        }

        if ($resolvedIncident !== null) {
            $this->notify($monitor, $resolvedIncident, resolved: true);
        }

        return $result;
    }

    private function openIncident(Monitor $monitor, ?Carbon $startedAt, ?string $cause): Incident
    {
        return $monitor->incidents()->create([
            'started_at' => $startedAt ?? now(),
            'cause' => $cause,
        ]);
    }

    private function resolveOpenIncident(Monitor $monitor, ?Carbon $resolvedAt): ?Incident
    {
        $incident = $monitor->incidents()
            ->whereNull('resolved_at')
            ->latest('started_at')
            ->first();

        if ($incident === null) {
            return null;
        }

        $resolvedAt ??= now();

        $incident->update([
            'resolved_at' => $resolvedAt,
            'duration_seconds' => max(0, (int) $resolvedAt->diffInSeconds($incident->started_at, true)),
        ]);

        return $incident;
    }

    private function notify(Monitor $monitor, Incident $incident, bool $resolved): void
    {
        $recipient = config('monitoring.alert_email');

        if (! is_string($recipient) || trim($recipient) === '') {
            return;
        }

        try {
            Mail::to($recipient)->send(new IncidentAlertMail($monitor, $incident, $resolved));
        } catch (Throwable $exception) {
            Log::warning('Email insiden gagal dikirim', [
                'monitor_id' => $monitor->id,
                'incident_id' => $incident->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
