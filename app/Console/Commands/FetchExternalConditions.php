<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DiveSite;
use App\Models\ExternalCondition;
use App\Services\OpenMeteoService;
use Carbon\Carbon;

class FetchExternalConditions extends Command
{
    protected $signature = 'vizzbud:fetch-conditions';
    protected $description = 'Fetch and store external conditions for each dive site';

    public function handle(OpenMeteoService $weather)
    {
        $this->info('Fetching conditions...');

        DiveSite::all()->each(function ($site) use ($weather) {
            $data = $weather->fetchConditions((float) $site->lat, (float) $site->lng);
            $status = $data['status'] ?? null;

            if ($data) {
                ExternalCondition::create([
                    'dive_site_id' => $site->id,
                    'retrieved_at' => now(),
                    'status' => $data['status'] ?? null,
                    'wave_height' => $data['hours'][0]['waveHeight']['noaa'] ?? null,
                    'wave_period' => $data['hours'][0]['wavePeriod']['noaa'] ?? null,
                    'wave_direction' => $data['hours'][0]['waveDirection']['noaa'] ?? null,
                    'water_temperature' => $data['hours'][0]['waterTemperature']['noaa'] ?? null,
                    'wind_speed' => $data['hours'][0]['windSpeed']['noaa'] ?? null,
                    'wind_direction' => $data['hours'][0]['windDirection']['noaa'] ?? null,
                    'air_temperature' => $data['hours'][0]['airTemperature']['noaa'] ?? null,
                ]);

                $this->info("Stored conditions for {$site->name}");
            } else {
                $this->warn("Failed to fetch for {$site->name}");
            }
        });
    }
}