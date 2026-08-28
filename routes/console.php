<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
*/

// Keep trial/read-only/locked transitions deterministic under the canonical
// 27-day lifecycle: 7-day trial + 14-day read-only + 6-day locked.
Schedule::command('subscriptions:check-status')->hourly();

// Permanently purge tenants after the 6-day locked period.
Schedule::command('subscriptions:purge-expired --force')->dailyAt('02:30');

// Canonical lifecycle reminder emails: trial, read-only, locked, deletion window.
Schedule::command('subscriptions:send-lifecycle-reminders')->dailyAt('09:00');

// Process appointment reminders every 15 minutes.
Schedule::command('reminders:process')->everyFifteenMinutes();

// Aggregate analytics data daily at 00:30 (for yesterday).
Schedule::command('analytics:aggregate')->dailyAt('00:30');

// Dispatch trial nudge emails daily at 09:00.
Schedule::command('trial:nudges')->dailyAt('09:00');
