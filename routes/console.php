<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\FetchExternalConditions;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(FetchExternalConditions::class)
    ->cron('0 1,7,13,19 * * *') // 1am, 7am, 1pm, 7pm
    ->runInBackground();