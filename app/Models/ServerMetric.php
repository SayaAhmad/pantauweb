<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetric extends Model
{
    /** @use HasFactory<\Database\Factories\ServerMetricFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'cpu_percent',
        'ram_percent',
        'disk_percent',
        'load_average',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu_percent' => 'float',
            'ram_percent' => 'float',
            'disk_percent' => 'float',
            'load_average' => 'float',
            'collected_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
