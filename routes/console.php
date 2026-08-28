<?php

declare(strict_types=1);

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

// Keep trial/read-only/locked transitions deterministic.
Schedule::command('subscriptions:check-status')->hourly();

// Permanently purge tenants after the 30-day locked period.
Schedule::command('subscriptions:purge-expired --force')->dailyAt('02:30');

// Canonical lifecycle reminder emails: trial, read-only, and deletion window.
Schedule::command('subscriptions:send-lifecycle-reminders')->dailyAt('09:00');

// Process appointment reminders every 15 minutes.
Schedule::command('reminders:process')->everyFifteenMinutes();

// Aggregate analytics data daily at 00:30 (for yesterday).
Schedule::command('analytics:aggregate')->dailyAt('00:30');

// Dispatch trial nudge emails daily at 09:00.
Schedule::command('trial:nudges')->dailyAt('09:00');
