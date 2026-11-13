<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    /** @use HasFactory<\Database\Factories\ServerFactory> */
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'hostname',
        'ip',
        'description',
        'agent_token_hash',
        'last_seen_at',
    ];

    protected $hidden = [
        'agent_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }
}
