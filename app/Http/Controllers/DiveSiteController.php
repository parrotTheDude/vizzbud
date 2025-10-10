<?php

namespace App\Http\Controllers;

use App\Models\DiveSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class DiveSiteController extends Controller
{
    /**
     * List sites + latest condition + next 48h forecast + day-part summaries.
     * - Optional bbox filter (?bbox=minLng,minLat,maxLng,maxLat)
     * - Caches formatted payload for 60s
     */
    public function index(Request $request)
    {
        // Optional map bounding box filter
        [$minLng, $minLat, $maxLng, $maxLat] = array_pad(
            explode(',', (string) $request->query('bbox', '')), 4, null
        );

        $cacheKey = 'sites:index:' . md5(implode(',', [$minLng, $minLat, $maxLng, $maxLat]));

        $payload = Cache::remember($cacheKey, 60, function () use ($minLng, $minLat, $maxLng, $maxLat) {
            $sitesQuery = DiveSite::query()
                ->select([
                    'id','slug','name','description','lat','lng',
                    'max_depth','avg_depth','dive_type','suitability','timezone',
                ])
                ->with([
                    // Latest snapshot
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
                    // Next 48h hourly forecast
                    'forecasts' => function ($q) {
                        $q->select([
                            'id','dive_site_id','forecast_time',
                            'wave_height','wave_period','wave_direction',
                            'water_temperature','wind_speed','wind_direction',
                            'air_temperature','updated_at',
                        ])
                        ->where('forecast_time', '>=', Carbon::now()->startOfHour())
                        ->orderBy('forecast_time')
                        ->limit(48);
                    },
                    // Dayparts: pull a small forward window (we’ll filter per-site in PHP)
                    'dayparts' => function ($q) {
                        $q->select(['id','dive_site_id','local_date','part','status'])
                        ->orderBy('local_date', 'desc')
                        ->orderBy('part')
                        ->limit(12);
                    },
                ]);

            if ($minLng !== null && $minLat !== null && $maxLng !== null && $maxLat !== null) {
                $sitesQuery
                    ->whereBetween('lng', [(float)$minLng, (float)$maxLng])
                    ->whereBetween('lat', [(float)$minLat, (float)$maxLat]);
            }

            $sites = $sitesQuery->get();

            $toIso = static fn ($v) => $v ? Carbon::parse($v)->toDateTimeString() : null;

            $formattedSites = $sites->map(function ($site) use ($toIso) {
                $c   = $site->latestCondition;
                $tz  = $site->timezone ?: 'UTC';
                $todayLocal = Carbon::now($tz)->toDateString();

                // Build dayparts grouped per local_date => {morning, afternoon, night}
                $dayparts = $site->dayparts
                    ->groupBy(fn ($r) => (string)$r->local_date)
                    ->map(function ($rows, $date) {
                        $byPart = $rows->pluck('status', 'part');
                        return [
                            'date'      => \Illuminate\Support\Carbon::parse($date)->toDateString(),
                            'morning'   => $byPart['morning']   ?? null,
                            'afternoon' => $byPart['afternoon'] ?? null,
                            'night'     => $byPart['night']     ?? null,
                        ];
                    })
                    ->filter(function ($d) use ($todayLocal) {
                        return $d['date'] >= $todayLocal;
                    })
                    ->sortBy('date')
                    ->values()
                    ->take(3);

                $todayRow = collect($dayparts)->first(fn($d) => $d['date'] === $todayLocal);

                $todaySummary = $todayRow
                    ? collect($todayRow)->only(['morning','afternoon','night'])
                    : null;

                return [
                    'id'          => $site->id,
                    'slug'        => $site->slug,
                    'name'        => $site->name,
                    'description' => $site->description,
                    'lat'         => (float) $site->lat,
                    'lng'         => (float) $site->lng,
                    'max_depth'   => $site->max_depth,
                    'avg_depth'   => $site->avg_depth,
                    'dive_type'   => $site->dive_type,
                    'suitability' => $site->suitability,
                    'timezone'    => $tz,

                    'retrieved_at'=> $toIso(optional($c)->retrieved_at),
                    'status'      => $c?->status,

                    'conditions'  => $c ? [
                        'waveHeight'       => ['noaa' => $c->wave_height],
                        'wavePeriod'       => ['noaa' => $c->wave_period],
                        'waveDirection'    => ['noaa' => $c->wave_direction],
                        'waterTemperature' => ['noaa' => $c->water_temperature],
                        'windSpeed'        => ['noaa' => $c->wind_speed],
                        'windDirection'    => ['noaa' => $c->wind_direction],
                        'airTemperature'   => ['noaa' => $c->air_temperature],
                    ] : null,

                    'forecast' => $site->forecasts->map(static function ($f) use ($toIso) {
                        return [
                            'forecast_time'     => $toIso($f->forecast_time),
                            'wave_height'       => $f->wave_height,
                            'wave_period'       => $f->wave_period,
                            'wave_direction'    => $f->wave_direction,
                            'water_temperature' => $f->water_temperature,
                            'wind_speed'        => $f->wind_speed,
                            'wind_direction'    => $f->wind_direction,
                            'air_temperature'   => $f->air_temperature,
                        ];
                    }),

                    'forecast_updated_at' => $toIso($site->forecasts->max('updated_at')),

                    'dayparts' => $dayparts->values(),
                    'today_summary' => $todaySummary,
                ];
            })->values();

            $siteOptions = DiveSite::query()
                ->select(['id','name'])
                ->orderBy('name')
                ->get();

            return compact('formattedSites', 'siteOptions');
        });

        return view('dive-sites.index', [
            'sites'       => $payload['formattedSites'],
            'siteOptions' => $payload['siteOptions'],
        ]);
    }

    public function show(DiveSite $diveSite)
    {
        $tz = $diveSite->timezone ?: 'UTC';
        $todayLocal = \Illuminate\Support\Carbon::now($tz)->toDateString();

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
                ->where('forecast_time', '>=', \Illuminate\Support\Carbon::now()->startOfHour())
                ->orderBy('forecast_time')
                ->limit(48),

            // Only today’s parts in local tz (we store as local_date)
            'dayparts' => fn($q) => $q->select(['id','dive_site_id','local_date','part','status'])
                ->where('local_date', $todayLocal),
        ]);

        return view('dive-sites.show', compact('diveSite'));
    }
}