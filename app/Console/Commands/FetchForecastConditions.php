<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DiveSite;
use App\Models\ExternalConditionForecast;
use App\Services\OpenMeteoService;
use Carbon\Carbon;

class FetchForecastConditions extends Command
{
    protected $signature = 'vizzbud:fetch-forecast';
    protected $description = 'Fetch and store forecasted external conditions for each dive site';

    public function handle(OpenMeteoService $weather)
    {
        $this->info('Fetching forecast conditions...');

        DiveSite::all()->each(function ($site) use ($weather) {
            $forecasts = $weather->fetchForecasts($site->lat, $site->lng);

            if (!$forecasts || empty($forecasts)) {
                $this->warn("No forecast data for {$site->name}");
                return;
            }

            foreach (array_slice($forecasts['hours'], 0, 48) as $entry) {
                ExternalConditionForecast::updateOrCreate(
                    [
                        'dive_site_id' => $site->id,
                        'forecast_time' => Carbon::parse($entry['time'])->toDateTimeString(),
                    ],
                    [
                        'wave_height'       => $entry['waveHeight'] ?? null,
                        'wave_period'       => $entry['wavePeriod'] ?? null,
                        'wave_direction'    => $entry['waveDirection'] ?? null,
                        'water_temperature' => $entry['waterTemperature'] ?? null,
                        'wind_speed'        => $entry['windSpeed'] ?? null,
                        'wind_direction'    => $entry['windDirection'] ?? null,
                        'air_temperature'   => $entry['airTemperature'] ?? null,
                    ]
                );
            }

            $this->info("Stored forecast for {$site->name}");
        });
    }
}