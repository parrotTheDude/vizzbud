<?php

namespace App\Http\Controllers;

use App\Models\DiveSite;
use App\Models\ExternalCondition;
use Carbon\Carbon;

class DiveSiteController extends Controller
{
    public function index()
    {
        $sites = DiveSite::with(['latestCondition', 'forecasts' => function ($q) {
            $q->orderBy('forecast_time')->take(48); // next 12 hours, adjust as needed
        }])->get();
    
        $formattedSites = $sites->map(function ($site) {
            $c = $site->latestCondition;
        
            return [
                'id' => $site->id,
                'name' => $site->name,
                'description' => $site->description,
                'lat' => (float) $site->lat,
                'lng' => (float) $site->lng,
                'max_depth' => $site->max_depth,
                'avg_depth' => $site->avg_depth,
                'dive_type' => $site->dive_type,
                'suitability' => $site->suitability,
                'retrieved_at' => optional($c)->retrieved_at?->toDateTimeString(),
        
                'conditions' => $c ? [
                    'waveHeight'     => ['noaa' => $c->wave_height],
                    'wavePeriod'     => ['noaa' => $c->wave_period],
                    'waveDirection'  => ['noaa' => $c->wave_direction],
                    'waterTemperature' => ['noaa' => $c->water_temperature],
                    'windSpeed'      => ['noaa' => $c->wind_speed],
                    'windDirection'  => ['noaa' => $c->wind_direction],
                    'airTemperature' => ['noaa' => $c->air_temperature],
                ] : null,
        
                'forecast' => $site->forecasts->map(function ($f) {
                    return [
                        'time' => Carbon::parse($f->forecast_time)->toDateTimeString(),
                        'waveHeight'     => ['noaa' => $f->wave_height],
                        'wavePeriod'     => ['noaa' => $f->wave_period],
                        'waveDirection'  => ['noaa' => $f->wave_direction],
                        'waterTemperature' => ['noaa' => $f->water_temperature],
                        'windSpeed'      => ['noaa' => $f->wind_speed],
                        'windDirection'  => ['noaa' => $f->wind_direction],
                        'airTemperature' => ['noaa' => $f->air_temperature],
                    ];
                }),
            ];
        });
        
        $siteOptions = DiveSite::select('id', 'name')->orderBy('name')->get();
    
        return view('dive-sites.index', [
            'sites' => $formattedSites,
            'siteOptions' => $siteOptions,
        ]);
    }
}