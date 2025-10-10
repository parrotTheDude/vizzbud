<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\FetchExternalConditions;

// -----------------------------------------------------------------------------
// Scheduled Jobs
// -----------------------------------------------------------------------------

// Fetch latest external marine/weather conditions
Schedule::command(FetchExternalConditions::class)
    ->hourly()
    ->runInBackground();

// Fetch 3-day forecasts (for all dive sites)
Schedule::command('vizzbud:fetch-forecast')
    ->dailyAt('03:00')
    ->timezone('Australia/Sydney');

// Recompute daily “morning/afternoon/night” summaries from forecast data
Schedule::command('vizzbud:build-dayparts')
    ->dailyAt('04:00')
    ->timezone('Australia/Sydney')
    ->runInBackground();

// Backfill missing or new site timezones (idempotent)
Schedule::command('vizzbud:backfill-timezones')
    ->dailyAt('00:05')
    ->timezone('Australia/Sydney')
    ->runInBackground();

// Clean up unverified users
Schedule::command('users:cleanup-unverified')
    ->dailyAt('00:00')
    ->timezone('Australia/Sydney');