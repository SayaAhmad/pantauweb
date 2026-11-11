<?php

namespace App\Enums;

enum MonitorType: string
{
    case Http = 'http';
    case Port = 'port';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Http => 'Website',
            self::Port => 'Port',
            self::Service => 'Layanan',
        };
    }

    public function requiresUrl(): bool
    {
        return $this !== self::Port;
    }
}
