<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('recordatorios:proximas')->dailyAt('09:00');
Schedule::command('recordatorios:vencidas')->dailyAt('09:15');

Schedule::call(fn() => @touch(storage_path('app/scheduler-heartbeat')))
    ->everyMinute()
    ->name('scheduler-heartbeat');
