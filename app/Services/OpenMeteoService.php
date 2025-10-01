<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenMeteoService
{
    // Tunables (can move to config/vizzbud.php if you prefer)
    private int $timeout   = 8;   // seconds
    private int $retries   = 2;   // extra tries (total 3)
    private int $cacheTtl  = 300; // seconds
    private bool $includeTide = true;

    // Status thresholds (can override via env/config)
    private float $greenMaxWaveM  = 1.0;
    private float $greenMaxWindKt = 10.0;
    private float $yellowMaxWaveM = 2.0;
    private float $yellowMaxWindKt = 15.0;

    public function __construct()
    {
        // Optional: read from env
        $this->greenMaxWaveM   = (float) env('VIZZBUD_GREEN_MAX_WAVE_M',  $this->greenMaxWaveM);
        $this->greenMaxWindKt  = (float) env('VIZZBUD_GREEN_MAX_WIND_KT', $this->greenMaxWindKt);
        $this->yellowMaxWaveM  = (float) env('VIZZBUD_YELLOW_MAX_WAVE_M', $this->yellowMaxWaveM);
        $this->yellowMaxWindKt = (float) env('VIZZBUD_YELLOW_MAX_WIND_KT',$this->yellowMaxWindKt);
    }

    /**
     * Fetch “current-ish” conditions for a single point.
     * Returns: ['status' => ..., 'hours' => [[ '...noaa' => value ]]]
     */
    public function fetchConditions(float $lat, float $lng): ?array
    {
        $cacheKey = "omc:" . sprintf('%.4f,%.4f', $lat, $lng);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($lat, $lng) {
            try {
                $responses = Http::pool(function ($pool) use ($lat, $lng) {
                    $marineParams = [
                        'latitude'      => $lat,
                        'longitude'     => $lng,
                        'hourly'        => 'wave_height,wave_period,wave_direction,sea_surface_temperature',
                        'timezone'      => 'UTC', // stable indexing
                        'forecast_days' => 1,
                    ];

                    $weatherParams = [
                        'latitude'       => $lat,
                        'longitude'      => $lng,
                        'hourly'         => 'wind_speed_10m,wind_direction_10m,temperature_2m',
                        'windspeed_unit' => 'kn', // <-- get KN directly
                        'timezone'       => 'UTC',
                        'forecast_days'  => 1,
                    ];

                    $reqs = [
                        'marine'  => $pool->as('marine')
                            ->timeout($this->timeout)
                            ->retry($this->retries, 200)
                            ->get('https://marine-api.open-meteo.com/v1/marine', $marineParams),

                        'weather' => $pool->as('weather')
                            ->timeout($this->timeout)
                            ->retry($this->retries, 200)
                            ->get('https://api.open-meteo.com/v1/forecast', $weatherParams),
                    ];

                    if ($this->includeTide) {
                        $reqs['tide'] = $pool->as('tide')
                            ->timeout($this->timeout)
                            ->retry($this->retries, 200)
                            ->get('https://marine-api.open-meteo.com/v1/tide', [
                                'latitude'  => $lat,
                                'longitude' => $lng,
                                'timezone'  => 'UTC',
                            ]);
                    }

                    return $reqs;
                });

                $marine  = $responses['marine'];
                $weather = $responses['weather'];

                if (!$marine->ok() || !$weather->ok()) {
                    Log::error('OpenMeteo fetch failed', [
                        'lat' => $lat, 'lng' => $lng,
                        'marine_ok' => $marine->ok(), 'weather_ok' => $weather->ok(),
                        'marine_body' => $marine->body(), 'weather_body' => $weather->body(),
                    ]);
                    return null;
                }

                $marineData  = $marine->json();
                $weatherData = $weather->json();

                // Nearest-hour index (don’t assume [0])
                [$i, $iso] = $this->nearestHourIndex(
                    Arr::get($marineData, 'hourly.time', []),
                    Arr::get($weatherData, 'hourly.time', [])
                );
                if ($i === null) {
                    return null;
                }

                // Marine
                $waveHeightM = Arr::get($marineData,  "hourly.wave_height.$i");
                $wavePeriodS = Arr::get($marineData,  "hourly.wave_period.$i");
                $waveDirDeg  = Arr::get($marineData,  "hourly.wave_direction.$i");
                $waterTempC  = Arr::get($marineData,  "hourly.sea_surface_temperature.$i");

                // Weather (already in knots due to windspeed_unit=kn)
                $windSpeedKt = Arr::get($weatherData, "hourly.wind_speed_10m.$i");
                $windDirDeg  = Arr::get($weatherData, "hourly.wind_direction_10m.$i");
                $airTempC    = Arr::get($weatherData, "hourly.temperature_2m.$i");

                $status = $this->computeStatus(
                    $waveHeightM !== null ? (float)$waveHeightM : null,
                    $windSpeedKt !== null ? (float)$windSpeedKt : null
                );

                return [
                    'status' => $status,
                    'hours'  => [[
                        'time'             => $iso,
                        'waveHeight'       => ['noaa' => $this->maybeRound($waveHeightM, 2)],
                        'wavePeriod'       => ['noaa' => $this->maybeRound($wavePeriodS, 1)],
                        'waveDirection'    => ['noaa' => $this->maybeRound($waveDirDeg, 0)],
                        'waterTemperature' => ['noaa' => $this->maybeRound($waterTempC, 1)],
                        'windSpeed'        => ['noaa' => $this->maybeRound($windSpeedKt, 1)], // KNOTS
                        'windDirection'    => ['noaa' => $this->maybeRound($windDirDeg, 0)],
                        'airTemperature'   => ['noaa' => $this->maybeRound($airTempC, 1)],
                    ]],
                ];
            } catch (Throwable $e) {
                Log::warning('OpenMeteoService exception', [
                    'lat' => $lat, 'lng' => $lng, 'err' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Full multi-hour forecast (knots for wind).
     * Returns: ['hours' => [...]]
     */
    public function fetchForecasts(float $lat, float $lng): array
    {
        try {
            $responses = Http::pool(function ($pool) use ($lat, $lng) {
                return [
                    'marine' => $pool->as('marine')
                        ->timeout($this->timeout)->retry($this->retries, 200)
                        ->get('https://marine-api.open-meteo.com/v1/marine', [
                            'latitude'      => $lat,
                            'longitude'     => $lng,
                            'hourly'        => 'wave_height,wave_period,wave_direction,sea_surface_temperature',
                            'timezone'      => 'UTC',
                            'forecast_days' => 3,
                        ]),
                    'weather' => $pool->as('weather')
                        ->timeout($this->timeout)->retry($this->retries, 200)
                        ->get('https://api.open-meteo.com/v1/forecast', [
                            'latitude'       => $lat,
                            'longitude'      => $lng,
                            'hourly'         => 'wind_speed_10m,wind_direction_10m,temperature_2m',
                            'windspeed_unit' => 'kn', // <-- knots here too
                            'timezone'       => 'UTC',
                            'forecast_days'  => 3,
                        ]),
                ];
            });

            $marine  = $responses['marine'];
            $weather = $responses['weather'];
            if (!$marine->ok() || !$weather->ok()) {
                Log::warning('OpenMeteo forecast fetch failed', [
                    'lat' => $lat, 'lng' => $lng,
                    'marine_ok' => $marine->ok(), 'weather_ok' => $weather->ok(),
                ]);
                return [];
            }

            $m = $marine->json();
            $w = $weather->json();

            $times = Arr::get($m, 'hourly.time', []);
            $result = [];

            foreach ($times as $i => $iso) {
                $windKt = Arr::get($w, "hourly.wind_speed_10m.$i"); // already knots

                $result[] = [
                    'time'             => $iso,
                    'waveHeight'       => Arr::get($m, "hourly.wave_height.$i"),
                    'wavePeriod'       => Arr::get($m, "hourly.wave_period.$i"),
                    'waveDirection'    => Arr::get($m, "hourly.wave_direction.$i"),
                    'waterTemperature' => Arr::get($m, "hourly.sea_surface_temperature.$i"),
                    'windSpeed'        => $windKt, // knots
                    'windDirection'    => Arr::get($w, "hourly.wind_direction_10m.$i"),
                    'airTemperature'   => Arr::get($w, "hourly.temperature_2m.$i"),
                ];
            }

            return ['hours' => $result];
        } catch (Throwable $e) {
            Log::warning('OpenMeteoService::fetchForecasts exception', [
                'lat' => $lat, 'lng' => $lng, 'err' => $e->getMessage()
            ]);
            return [];
        }
    }

    // ---- helpers ----

    private function computeStatus(?float $waveM, ?float $windKt): string
    {
        if ($waveM === null || $windKt === null) {
            return 'red';
        }
        if ($waveM < $this->greenMaxWaveM && $windKt < $this->greenMaxWindKt) {
            return 'green';
        }
        if ($waveM < $this->yellowMaxWaveM && $windKt < $this->yellowMaxWindKt) {
            return 'yellow';
        }
        return 'red';
    }

    /**
     * Returns [index, iso_time] for the nearest hour across marine/weather arrays.
     */
    private function nearestHourIndex(array $marineTimes, array $weatherTimes): array
    {
        if (empty($marineTimes)) {
            return [null, null];
        }

        $nowIso  = gmdate('Y-m-d\TH:00:00');
        $bestIdx = null;
        $bestDiff = PHP_INT_MAX;

        foreach ($marineTimes as $i => $iso) {
            $diff = abs(strtotime($iso) - strtotime($nowIso));
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $bestIdx  = $i;
            }
        }

        // Ensure weather arrays have this index; if not, fallback to 0 safely
        if ($bestIdx === null || !isset($weatherTimes[$bestIdx])) {
            $bestIdx = 0;
        }

        return [$bestIdx, $marineTimes[$bestIdx] ?? $nowIso];
    }

    private function maybeRound($val, int $precision)
    {
        return $val === null ? null : round((float)$val, $precision);
    }
}