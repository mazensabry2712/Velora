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
