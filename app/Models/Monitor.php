<?php

namespace App\Models;

use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    /** @use HasFactory<\Database\Factories\MonitorFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'name',
        'type',
        'url',
        'host',
        'port',
        'method',
        'expected_status',
        'expected_keyword',
        'interval_seconds',
        'timeout_seconds',
        'confirmation_threshold',
        'recovery_threshold',
        'is_active',
        'status',
        'consecutive_failures',
        'consecutive_successes',
        'failure_streak_started_at',
        'recovery_streak_started_at',
        'last_checked_at',
        'next_check_at',
        'last_response_ms',
        'last_http_status',
        'last_error',
        'ssl_expires_at',
        'ssl_issuer',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => MonitorType::class,
            'status' => MonitorStatus::class,
            'is_active' => 'boolean',
            'port' => 'integer',
            'expected_status' => 'integer',
            'interval_seconds' => 'integer',
            'timeout_seconds' => 'integer',
            'confirmation_threshold' => 'integer',
            'recovery_threshold' => 'integer',
            'consecutive_failures' => 'integer',
            'consecutive_successes' => 'integer',
            'last_response_ms' => 'integer',
            'last_http_status' => 'integer',
            'failure_streak_started_at' => 'datetime',
            'recovery_streak_started_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function dailyRollups(): HasMany
    {
        return $this->hasMany(DailyRollup::class);
    }

    public function openIncident(): ?Incident
    {
        return $this->incidents()->whereNull('resolved_at')->latest('started_at')->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNull('next_check_at')->orWhere('next_check_at', '<=', now());
        });
    }

    public function target(): string
    {
        return $this->type === MonitorType::Port
            ? $this->host.':'.$this->port
            : (string) $this->url;
    }

    public function isSslWarning(): bool
    {
        return $this->ssl_expires_at !== null
            && $this->ssl_expires_at->isBefore(now()->addDays(30));
    }
}
