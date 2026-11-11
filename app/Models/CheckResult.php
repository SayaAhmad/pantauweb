<?php

namespace App\Models;

use App\Enums\CheckStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckResult extends Model
{
    /** @use HasFactory<\Database\Factories\CheckResultFactory> */
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'status',
        'http_status',
        'response_time_ms',
        'error_message',
        'ssl_expires_at',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CheckStatus::class,
            'http_status' => 'integer',
            'response_time_ms' => 'integer',
            'ssl_expires_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
