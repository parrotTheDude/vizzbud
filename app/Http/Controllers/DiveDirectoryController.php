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
        $now = Carbon::now($tz);

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
                    'external_condition_forecasts.id',
                    'external_condition_forecasts.dive_site_id',
                    'external_condition_forecasts.forecast_time',
                    'external_condition_forecasts.wave_height',
                    'external_condition_forecasts.wave_period',
                    'external_condition_forecasts.wave_direction',
                    'external_condition_forecasts.water_temperature',
                    'external_condition_forecasts.wind_speed',
                    'external_condition_forecasts.wind_direction',
                    'external_condition_forecasts.air_temperature',
                ])
                ->where('external_condition_forecasts.forecast_time', '>=', Carbon::now()->startOfHour())
                ->orderBy('external_condition_forecasts.forecast_time')
                ->limit(72),
            'dayparts' => fn($q) => $q->select([
                    'external_condition_dayparts.id',
                    'external_condition_dayparts.dive_site_id',
                    'external_condition_dayparts.local_date',
                    'external_condition_dayparts.part',
                    'external_condition_dayparts.status'
                ])
                ->where('external_condition_dayparts.local_date', '>=', Carbon::now()->toDateString())
                ->orderBy('external_condition_dayparts.local_date')
                ->orderBy('external_condition_dayparts.part')
                ->limit(9),
        ]);

        $nearbySites = $diveSite->nearbySites(3);

        // ---- Build daypart forecasts with real values ----
        $forecasts = $diveSite->forecasts;
        $grouped = [];

        foreach ($diveSite->dayparts->groupBy('local_date') as $date => $rows) {
            $grouped[$date] = [
                'date' => $date,
                'morning'   => $this->summarizeForecastWindow($diveSite, $forecasts, $date, 6, 11, $rows->firstWhere('part', 'morning')?->status),
                'afternoon' => $this->summarizeForecastWindow($diveSite, $forecasts, $date, 12, 16, $rows->firstWhere('part', 'afternoon')?->status),
                'night'     => $this->summarizeForecastWindow($diveSite, $forecasts, $date, 17, 21, $rows->firstWhere('part', 'night')?->status),
            ];
        }

        $daypartForecasts = array_values(collect($grouped)->take(3)->sortKeys()->toArray());

        log_activity('divesite_viewed', $diveSite, [
            'slug' => $diveSite->slug,
            'name' => $diveSite->name,
        ]);

        return view('dive-sites.show', [
            'diveSite' => $diveSite,
            'daypartForecasts' => $daypartForecasts,
            'nearbySites' => $nearbySites,
        ]);
    }

    /**
    * Summarize wave / wind values for one time window
    */
    private function summarizeForecastWindow($diveSite, $forecasts, $date, $startHour, $endHour, $status = null)
    {
        $subset = $forecasts->filter(fn($f) =>
            Carbon::parse($f->forecast_time)->isSameDay($date) &&
            Carbon::parse($f->forecast_time)->hour >= $startHour &&
            Carbon::parse($f->forecast_time)->hour <= $endHour
        );

        if ($subset->isEmpty()) {
            return ['status' => $status ?? 'unknown'];
        }

        $wave = round($subset->avg('wave_height'), 1);
        $waveMax = round($subset->max('wave_height'), 1);
        $period = round($subset->avg('wave_period'), 0);
        $wind = round($subset->avg('wind_speed') * 1.94384, 0); // m/s → kn

        // fallback: compute status if DB one missing
        if (!$status) {
            $status = 'green';
            if ($wave > 1.2 || $wind > 12) $status = 'yellow';
            if ($wave > 1.8 || $wind > 18) $status = 'red';
        }

        $wave_max = $diveSite->forecasts->max('wave_height');

        return compact('status', 'wave', 'wave_max', 'period', 'wind');
    }
}