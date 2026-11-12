<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('monitors:dispatch')->everyMinute()->withoutOverlapping(5);
Schedule::command('monitors:rollup')->dailyAt('00:15')->withoutOverlapping(30);
Schedule::command('monitors:prune')->dailyAt('02:00')->withoutOverlapping(60);
