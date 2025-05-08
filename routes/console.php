<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\FetchExternalConditions;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(FetchExternalConditions::class)
    ->hourly()
    ->runInBackground();

Schedule::command('vizzbud:fetch-forecast')
    ->dailyAt('06:01')
    ->timezone('Australia/Sydney');

Schedule::command('users:cleanup-unverified')
->dailyAt('00:00')
->timezone('Australia/Sydney');