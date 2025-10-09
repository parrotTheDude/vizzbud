<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DiveSite;
use Illuminate\Support\Facades\Http;
use Throwable;

class BackfillDiveSiteTimezones extends Command
{
    protected $signature = 'vizzbud:backfill-timezones';
    protected $description = 'Fill missing dive_site.timezone using Open-Meteo timezone lookup';

    public function handle()
    {
        $sites = DiveSite::whereNull('timezone')->orWhere('timezone', '')->get();

        if ($sites->isEmpty()) {
            $this->info('All dive sites already have a timezone.');
            return self::SUCCESS;
        }

        $this->info("Filling timezone for {$sites->count()} dive sites...");

        $bar = $this->output->createProgressBar($sites->count());
        $bar->start();

        foreach ($sites as $site) {
            try {
                $res = Http::get('https://api.open-meteo.com/v1/forecast', [
                    'latitude'  => $site->lat,
                    'longitude' => $site->lng,
                    'timezone'  => 'auto',
                    'forecast_days' => 1,
                ]);

                if ($res->ok()) {
                    $tz = $res->json('timezone');
                    if ($tz) {
                        $site->timezone = $tz;
                        $site->save();
                    }
                }
            } catch (Throwable $e) {
                $this->warn("Failed to fetch timezone for {$site->name}: {$e->getMessage()}");
            }

            $bar->advance();
            usleep(150000); // 0.15s pause to be polite
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Timezone backfill complete!');
        return self::SUCCESS;
    }
}