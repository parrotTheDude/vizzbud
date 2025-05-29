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
            $q->orderBy('forecast_time')->take(48);
        }])->get();
    
        $formattedSites = $sites->map(function ($site) {
            $c = $site->latestCondition;
        
            return [
                'id' => $site->id,
                'slug' => $site->slug,
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
                        'forecast_time'     => Carbon::parse($f->forecast_time)->toDateTimeString(),
                        'wave_height'       => $f->wave_height,
                        'wave_period'       => $f->wave_period,
                        'wave_direction'    => $f->wave_direction,
                        'water_temperature' => $f->water_temperature,
                        'wind_speed'        => $f->wind_speed,
                        'wind_direction'    => $f->wind_direction,
                        'air_temperature'   => $f->air_temperature,
                    ];
                }),
                
                'forecast_updated_at' => optional(
                    $site->forecasts->max('updated_at')
                )?->toDateTimeString(),
            ];
        });
        
        $siteOptions = DiveSite::select('id', 'name')->orderBy('name')->get();
    
        return view('dive-sites.index', [
            'sites' => $formattedSites,
            'siteOptions' => $siteOptions,
        ]);
    }

    public function show(DiveSite $diveSite)
    {
        $recentDives = $diveSite->diveLogs()->latest()->take(5)->get(); // if you have a relation
        return view('dive-sites.show', compact('diveSite', 'recentDives'));
    }
}