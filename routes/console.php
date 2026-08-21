<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:backup-database')->dailyAt('02:00');

Schedule::command('app:send-alerts')->hourly();

Schedule::command('notifications:prune')->daily();

Schedule::command('app:send-monthly-report')->monthlyOn(1, '07:00');
