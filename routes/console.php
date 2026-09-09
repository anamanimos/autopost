<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automation Engine Scheduler
Schedule::command('meta:publish')->everyMinute()->withoutOverlapping();
Schedule::command('meta:maintain-buffer')->hourly()->withoutOverlapping();
Schedule::command('meta:sync-accounts')->twiceDaily()->withoutOverlapping();