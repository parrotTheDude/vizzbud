<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\State;
use App\Models\Region;
use App\Models\DiveSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class DiveDirectoryController extends Controller
{
    public function countries()
    {
        $countries = Country::withCount('states')->orderBy('name')->get();
        return view('directory.countries', compact('countries'));
    }

    public function country($countrySlug)
    {
        $country = Country::where('slug', $countrySlug)->firstOrFail();

        $states = State::where('country_id', $country->id)
            ->withCount('regions')
            ->orderBy('name')
            ->get();

        return view('directory.state', compact('country', 'states'));
    }

    public function state($countrySlug, $stateSlug)
    {
        $country = Country::where('slug', $countrySlug)->firstOrFail();

        $state = State::where('slug', $stateSlug)
            ->where('country_id', $country->id)
            ->firstOrFail();

        $regions = Region::where('state_id', $state->id)
            ->withCount('diveSites')
            ->orderBy('name')
            ->get();

        return view('directory.region', compact('country', 'state', 'regions'));
    }

    public function region($countrySlug, $stateSlug, $regionSlug)
    {
        $country = Country::where('slug', $countrySlug)->firstOrFail();

        $state = State::where('slug', $stateSlug)
            ->where('country_id', $country->id)
            ->firstOrFail();

        $region = Region::where('slug', $regionSlug)
            ->where('state_id', $state->id)
            ->firstOrFail();

        $diveSites = DiveSite::where('region_id', $region->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('directory.sites', compact('country', 'state', 'region', 'diveSites'));
    }

    public function show($countrySlug, $stateSlug, $regionSlug, DiveSite $diveSite)
    {
        $tz = $diveSite->timezone ?: 'UTC';
        $todayLocal = Carbon::now($tz)->toDateString();

        $diveSite->loadMissing([
            'latestCondition' => function ($q) {
                $q->select([
                    'external_conditions.id',
                    'external_conditions.dive_site_id',
                    'external_conditions.retrieved_at',
                    'external_conditions.status',
                    'external_conditions.wave_height',
                    'external_conditions.wave_period',
                    'external_conditions.wave_direction',
                    'external_conditions.water_temperature',
                    'external_conditions.wind_speed',
                    'external_conditions.wind_direction',
                    'external_conditions.air_temperature',
                ]);
            },
            'forecasts' => fn($q) => $q->select([
                    'id','dive_site_id','forecast_time',
                    'wave_height','wave_period','wave_direction',
                    'water_temperature','wind_speed','wind_direction',
                    'air_temperature'
                ])
                ->where('forecast_time', '>=', Carbon::now()->startOfHour())
                ->orderBy('forecast_time')
                ->limit(48),
            'dayparts' => fn($q) => $q->select(['id','dive_site_id','local_date','part','status'])
                ->where('local_date', '>=', Carbon::now()->toDateString())
                ->orderBy('local_date')
                ->orderBy('part')
                ->limit(9),
        ]);

        $nearbySites = $diveSite->nearbySites(3);

        $daypartsGrouped = $diveSite->dayparts
            ->groupBy(fn ($r) => (string)$r->local_date)
            ->map(function ($rows, $date) {
                $byPart = $rows->pluck('status', 'part');
                return [
                    'date'      => Carbon::parse($date)->toDateString(),
                    'morning'   => $byPart['morning']   ?? null,
                    'afternoon' => $byPart['afternoon'] ?? null,
                    'night'     => $byPart['night']     ?? null,
                ];
            })
            ->sortKeys()
            ->take(3)
            ->values();

        log_activity('divesite_viewed', $diveSite, [
            'slug' => $diveSite->slug,
            'name' => $diveSite->name,
        ]);

        return view('dive-sites.show', [
            'diveSite' => $diveSite,
            'daypartForecasts' => $daypartsGrouped,
            'nearbySites' => $nearbySites,
        ]);
    }
}