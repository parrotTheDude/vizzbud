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
        $today = Carbon::now($tz)->toDateString();

        // Load only what we need
        $diveSite->loadMissing([
            'dayparts' => fn($q) => $q->select([
                    'id',
                    'dive_site_id',
                    'local_date',
                    'part',
                    'status',
                    'wave_height_max',
                    'wave_period_max',
                    'wind_speed_max',
                    'swell_dir_avg',
                    'wind_dir_avg',
                    'score'
                ])
                ->where('local_date', '>=', $today)
                ->orderBy('local_date')
                ->orderBy('part')
                ->limit(9),
            'latestCondition',
            'forecasts',
        ]);

        $nearbySites = $diveSite->nearbySites(3);

        // --- Build EXACT output from DB values ---
        $daypartForecasts = $diveSite->dayparts
            ->groupBy('local_date')
            ->map(function ($rows, $date) {

                // Normalise each DB row into simplified arrays
                $normalised = $rows->mapWithKeys(function ($row) {
                    $key = strtolower(trim($row->part));

                    return [
                        $key => [
                            'status'          => $row->status,
                            'wave_height_max' => $row->wave_height_max,
                            'wave_period_max' => $row->wave_period_max,
                            'wind_speed_max'  => $row->wind_speed_max,
                            'swell_dir_avg'   => $row->swell_dir_avg,
                            'wind_dir_avg'    => $row->wind_dir_avg,
                            'score'           => $row->score,
                        ]
                    ];
                });

                return [
                    'date'      => $date,
                    'morning'   => $normalised['morning']   ?? null,
                    'afternoon' => $normalised['afternoon'] ?? null,
                    'night'     => $normalised['night']     ?? null,
                ];
            })
            ->sortKeys()
            ->values()
            ->take(3)
            ->toArray();

        log_activity('divesite_viewed', $diveSite, [
            'slug' => $diveSite->slug,
            'name' => $diveSite->name,
        ]);

        return view('dive-sites.show', [
            'diveSite'          => $diveSite,
            'daypartForecasts'  => $daypartForecasts,
            'nearbySites'       => $nearbySites,
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