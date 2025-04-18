<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class StormGlassService
{
    public function fetchConditions($lat, $lng)
{
    $response = Http::withHeaders([
        'Authorization' => env('STORMGLASS_API_KEY'),
    ])->get("https://api.stormglass.io/v2/weather/point", [
        'lat' => $lat,
        'lng' => $lng,
        'params' => implode(',', [
            'waterTemperature',
            'waveHeight',
            'wavePeriod',
            'windSpeed',
            'windDirection',
            'visibility'
        ]),
        'source' => 'noaa',
    ]);

    // TEMP DEBUG
    if (!$response->successful()) {
        logger()->error('StormGlass failed:', [
            'lat' => $lat,
            'lng' => $lng,
            'code' => $response->status(),
            'body' => $response->body(),
        ]);
    }

    return $response->successful() ? $response->json() : null;
}
}