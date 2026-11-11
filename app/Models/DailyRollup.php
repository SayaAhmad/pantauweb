<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyRollup extends Model
{
    /** @use HasFactory<\Database\Factories\DailyRollupFactory> */
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'date',
        'total_checks',
        'successful_checks',
        'avg_response_ms',
        'max_response_ms',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_checks' => 'integer',
            'successful_checks' => 'integer',
            'avg_response_ms' => 'integer',
            'max_response_ms' => 'integer',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function uptimePercent(): ?float
    {
        return $this->total_checks > 0
            ? round($this->successful_checks / $this->total_checks * 100, 2)
            : null;
    }
}
