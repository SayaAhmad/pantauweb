<?php

namespace App\Enums;

enum CheckStatus: string
{
    case Up = 'up';
    case Down = 'down';

    public function label(): string
    {
        return match ($this) {
            self::Up => 'Berhasil',
            self::Down => 'Gagal',
        };
    }
}
