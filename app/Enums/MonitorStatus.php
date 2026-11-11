<?php

namespace App\Enums;

enum MonitorStatus: string
{
    case Unknown = 'unknown';
    case Up = 'up';
    case Down = 'down';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Belum diperiksa',
            self::Up => 'Normal',
            self::Down => 'Gangguan',
        };
    }
}
