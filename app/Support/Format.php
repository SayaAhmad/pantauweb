<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class Format
{
    public static function datetime(?CarbonInterface $value): string
    {
        return $value === null
            ? '—'
            : $value->timezone(config('monitoring.display_timezone'))->translatedFormat('d M Y H:i');
    }

    public static function date(?CarbonInterface $value): string
    {
        return $value === null
            ? '—'
            : $value->timezone(config('monitoring.display_timezone'))->translatedFormat('d M Y');
    }

    public static function relative(?CarbonInterface $value): string
    {
        return $value === null
            ? 'belum pernah'
            : $value->diffForHumans(short: true);
    }

    public static function duration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        $seconds = max(0, $seconds);

        if ($seconds < 60) {
            return $seconds.' detik';
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            return $minutes.' menit';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $hours.' jam'.($remainingMinutes > 0 ? " {$remainingMinutes} menit" : '');
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return $days.' hari'.($remainingHours > 0 ? " {$remainingHours} jam" : '');
    }

    public static function responseTime(?int $milliseconds): string
    {
        if ($milliseconds === null) {
            return '—';
        }

        if ($milliseconds >= 1000) {
            return number_format($milliseconds / 1000, 2, ',', '.').' s';
        }

        return $milliseconds.' ms';
    }

    public static function percent(?float $value, int $precision = 2): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format($value, $precision, ',', '.').'%';
    }

    public static function secondsToInterval(int $seconds): string
    {
        if ($seconds % 3600 === 0 && $seconds >= 3600) {
            return intdiv($seconds, 3600).' jam';
        }

        if ($seconds % 60 === 0) {
            return intdiv($seconds, 60).' menit';
        }

        return $seconds.' detik';
    }
}
