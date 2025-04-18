<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenMeteoService
{
    public function fetchConditions(float $lat, float $lng): ?array
    {
        // 🌊 Marine Data
        $marineParams = [
            'latitude' => $lat,
            'longitude' => $lng,
            'hourly' => 'wave_height,wave_period,wave_direction,sea_surface_temperature',
            'timezone' => 'auto'
        ];

        $marine = Http::get('https://marine-api.open-meteo.com/v1/marine', $marineParams);

        if (!$marine->successful()) {
            logger()->error('OpenMeteo marine failed', ['lat' => $lat, 'lng' => $lng, 'body' => $marine->body()]);
            return null;
        }

        // 🌬️ Wind Data
        $weatherParams = [
            'latitude' => $lat,
            'longitude' => $lng,
            'hourly' => 'wind_speed_10m,wind_direction_10m',
            'timezone' => 'auto'
        ];

        $weather = Http::get('https://api.open-meteo.com/v1/forecast', $weatherParams);

        if (!$weather->successful()) {
            logger()->error('OpenMeteo weather failed', ['lat' => $lat, 'lng' => $lng, 'body' => $weather->body()]);
            return null;
        }

        // 🌒 Tide Data (optional, may not exist for all lat/lng)
        $tideParams = [
            'latitude' => $lat,
            'longitude' => $lng,
            'timezone' => 'auto'
        ];

        $tide = Http::get('https://marine-api.open-meteo.com/v1/tide', $tideParams);

        $tideHeights = null;
        $tideTrend = null;
        $nextHigh = null;
        $nextLow = null;
        $currentIndex = null;
        
        if ($tide->successful() && isset($tide->json()['hourly']['time'])) {
            $tideData = $tide->json();
            $tideTimes = $tideData['hourly']['time'];
            $tideHeights = $tideData['hourly']['tide_height'];
        
            $currentTime = now()->utc()->toIso8601String();
        
            foreach ($tideTimes as $i => $time) {
                if ($time > $currentTime) {
                    $currentIndex = $i;
                    break;
                }
            }

            if (is_null($currentIndex)) {
                logger()->warning('No valid tide index found', ['currentTime' => $currentTime, 'times' => $tideTimes]);
                return null;
            }
        
            if (isset($tideHeights[$currentIndex], $tideHeights[$currentIndex - 1])) {
                $tideTrend = ($tideHeights[$currentIndex] > $tideHeights[$currentIndex - 1]) ? 'rising' : 'falling';
            }
        
            for ($i = max(1, $currentIndex); $i < count($tideHeights) - 1; $i++) {
                // Look for local max (high tide)
                if (
                    $tideHeights[$i] > $tideHeights[$i - 1] &&
                    $tideHeights[$i] > $tideHeights[$i + 1] &&
                    !$nextHigh
                ) {
                    $nextHigh = [
                        'time' => $tideTimes[$i],
                        'height' => $tideHeights[$i]
                    ];
                }
            
                // Look for local min (low tide)
                if (
                    $tideHeights[$i] < $tideHeights[$i - 1] &&
                    $tideHeights[$i] < $tideHeights[$i + 1] &&
                    !$nextLow
                ) {
                    $nextLow = [
                        'time' => $tideTimes[$i],
                        'height' => $tideHeights[$i]
                    ];
                }
            
                if ($nextHigh && $nextLow) break;
            }
        } else {
            logger()->warning('Tide data not available', ['lat' => $lat, 'lng' => $lng, 'response' => $tide->body()]);
        }

        // 📊 Core data extraction
        $marineData = $marine->json();
        $weatherData = $weather->json();

        $waveHeight = $marineData['hourly']['wave_height'][0] ?? null;
        $windSpeedMps = $weatherData['hourly']['wind_speed_10m'][0] ?? null;
        $windSpeedKnots = $windSpeedMps ? $windSpeedMps * 1.94384 : null;

        $status = match (true) {
            $waveHeight !== null && $windSpeedKnots !== null && $waveHeight < 1 && $windSpeedKnots < 10 => 'green',
            $waveHeight !== null && $windSpeedKnots !== null && $waveHeight < 2 && $windSpeedKnots < 15 => 'yellow',
            default => 'red',
        };

        return [
            'status' => $status,
            'hours' => [[
                'time' => $marineData['hourly']['time'][0],
                'waveHeight' => ['noaa' => $waveHeight],
                'wavePeriod' => ['noaa' => $marineData['hourly']['wave_period'][0] ?? null],
                'waveDirection' => ['noaa' => $marineData['hourly']['wave_direction'][0] ?? null],
                'waterTemperature' => ['noaa' => $marineData['hourly']['sea_surface_temperature'][0] ?? null],
                'windSpeed' => ['noaa' => $windSpeedMps],
                'windDirection' => ['noaa' => $weatherData['hourly']['wind_direction_10m'][0] ?? null],
                'tideHeight' => ['noaa' => $tideHeights[$currentIndex] ?? null],
                'tideTrend' => $tideTrend,
                'nextHighTide' => $nextHigh,
                'nextLowTide' => $nextLow,
            ]]
        ];
    }
}