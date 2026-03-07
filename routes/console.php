<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
*/

// Check subscription status every hour (trial→grace, grace→expired transitions)
Schedule::command('subscriptions:check-status')->hourly();

// Send trial & grace reminder emails daily at 9 AM
Schedule::command('subscriptions:send-trial-reminders')->dailyAt('09:00');

// Process appointment reminders every 15 minutes
Schedule::command('reminders:process')->everyFifteenMinutes();

// Aggregate analytics data daily at 00:30 (for yesterday)
Schedule::command('analytics:aggregate')->dailyAt('00:30');

// Dispatch trial nudge emails daily at 09:00 (Day 1 / 3 / 7 / 12 of trial)
Schedule::command('trial:nudges')->dailyAt('09:00');
