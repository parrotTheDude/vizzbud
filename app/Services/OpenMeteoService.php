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
            'hourly' => 'wind_speed_10m,wind_direction_10m,temperature_2m',
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

        // 📊 Core data extraction
        $marineData = $marine->json();
        $weatherData = $weather->json();

        $waveHeight = $marineData['hourly']['wave_height'][0] ?? null;
        $windSpeedMps = $weatherData['hourly']['wind_speed_10m'][0] ?? null;
        $windSpeedKnots = $windSpeedMps ? $windSpeedMps * 1.94384 : null;
        $airTemp = $weatherData['hourly']['temperature_2m'][0] ?? null;

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
                'airTemperature' => ['noaa' => $airTemp],
            ]]
        ];
    }

    public function fetchForecasts(float $lat, float $lng): array
    {
        // 🌊 Marine Forecast Data
        $marineParams = [
            'latitude' => $lat,
            'longitude' => $lng,
            'hourly' => 'wave_height,wave_period,wave_direction,sea_surface_temperature',
            'timezone' => 'auto'
        ];
    
        $marine = Http::get('https://marine-api.open-meteo.com/v1/marine', $marineParams);
        if (!$marine->successful()) {
            logger()->warning('Marine forecast failed', ['lat' => $lat, 'lng' => $lng]);
            return [];
        }
    
        // 🌬️ Atmospheric Forecast Data
        $weatherParams = [
            'latitude' => $lat,
            'longitude' => $lng,
            'hourly' => 'wind_speed_10m,wind_direction_10m,temperature_2m',
            'timezone' => 'auto'
        ];
    
        $weather = Http::get('https://api.open-meteo.com/v1/forecast', $weatherParams);
        if (!$weather->successful()) {
            logger()->warning('Weather forecast failed', ['lat' => $lat, 'lng' => $lng]);
            return [];
        }
    
        $marineData = $marine->json();
        $weatherData = $weather->json();
    
        $times = $marineData['hourly']['time'] ?? [];
        $result = [];
    
        foreach ($times as $i => $time) {
            $result[] = [
                'time'             => $time,
                'waveHeight'       => $marineData['hourly']['wave_height'][$i] ?? null,
                'wavePeriod'       => $marineData['hourly']['wave_period'][$i] ?? null,
                'waveDirection'    => $marineData['hourly']['wave_direction'][$i] ?? null,
                'waterTemperature' => $marineData['hourly']['sea_surface_temperature'][$i] ?? null,
                'windSpeed'        => $weatherData['hourly']['wind_speed_10m'][$i] ?? null,
                'windDirection'    => $weatherData['hourly']['wind_direction_10m'][$i] ?? null,
                'airTemperature'   => $weatherData['hourly']['temperature_2m'][$i] ?? null,
            ];
        }
    
        return [
            'hours' => $result
        ];
    }
}