<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DiveSite;
use App\Models\ExternalConditionForecast;
use App\Services\OpenMeteoService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Throwable;

class FetchForecastConditions extends Command
{
    protected $signature = 'vizzbud:fetch-forecast
        {--site= : ID or slug of a specific site}
        {--hours=48 : How many hours ahead to store (<= 168 recommended)}
        {--rate=0 : Sleep milliseconds between site requests (API friendliness)}
        {--chunk=100 : Upsert batch size}
        {--dry-run : Fetch & log but do not write}
        {--prune=1 : Prune stale rows (1=yes, 0=no)}';

    protected $description = 'Fetch and store forecasted external conditions for dive sites (Open-Meteo)';

    public function handle(OpenMeteoService $weather)
    {
        $hours = max(1, (int)$this->option('hours'));
        $chunk = max(10, (int)$this->option('chunk'));
        $rateMs = max(0, (int)$this->option('rate'));
        $dryRun = (bool)$this->option('dry-run');
        $shouldPrune = (bool)$this->option('prune');

        $this->info("Fetching forecast conditions (horizon: {$hours}h, chunk: {$chunk}, rate: {$rateMs}ms, dry-run: " . ($dryRun ? 'yes' : 'no') . ").");

        // Resolve site scope
        $siteOpt = $this->option('site');
        $sitesQuery = DiveSite::query()
            ->when($siteOpt, function ($q) use ($siteOpt) {
                // accept numeric (ID) or slug/name string
                if (is_numeric($siteOpt)) {
                    $q->where('id', (int)$siteOpt);
                } else {
                    $q->where(function ($qq) use ($siteOpt) {
                        $qq->where('slug', $siteOpt)->orWhere('name', $siteOpt);
                    });
                }
            })
            ->select(['id', 'name', 'slug', 'lat', 'lng'])
            ->orderBy('id');

        $count = (clone $sitesQuery)->count();
        if ($count === 0) {
            $this->warn('No dive sites matched.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $now = CarbonImmutable::now('UTC')->floorHour(); // normalize alignment to UTC hour

        // Optionally prune stale rows first (older than "now" are in the past)
        if ($shouldPrune && !$dryRun) {
            $deleted = ExternalConditionForecast::where('forecast_time', '<', $now->toDateTimeString())->delete();
            $this->line(PHP_EOL . "Pruned {$deleted} past forecasts.");
        }

        $totals = [
            'sites_ok' => 0,
            'sites_fail' => 0,
            'rows_upserted' => 0,
            'rows_skipped' => 0,
        ];

        // Stream through sites to keep memory low
        $sitesQuery->cursor()->each(function (DiveSite $site) use (
            $weather, $hours, $chunk, $dryRun, $rateMs, $now, $bar, &$totals
        ) {
            try {
                // Basic guard for coordinates
                if (!is_numeric($site->lat) || !is_numeric($site->lng)) {
                    $this->warn(PHP_EOL . "Skipping {$site->name}: invalid coordinates.");
                    $totals['sites_fail']++;
                    $bar->advance();
                    return;
                }

                // Retry wrapper (simple linear backoff)
                $forecasts = $this->retry(3, function () use ($weather, $site) {
                    return $weather->fetchForecasts($site->lat, $site->lng);
                }, 200);

                if (!$forecasts || empty($forecasts['hours'])) {
                    $this->warn(PHP_EOL . "No forecast data for {$site->name}");
                    $totals['sites_fail']++;
                    $bar->advance();
                    return;
                }

                // Take horizon hours
                $hoursData = array_slice($forecasts['hours'], 0, $hours);

                // Map to rows (normalize keys + UTC hour)
                $rows = [];
                foreach ($hoursData as $entry) {
                    $t = Arr::get($entry, 'time');
                    if (!$t) {
                        $totals['rows_skipped']++;
                        continue;
                    }

                    // Normalize to UTC hour
                    $ts = CarbonImmutable::parse($t)->utc()->floorHour()->toDateTimeString();

                    $rows[] = [
                        'dive_site_id'      => $site->id,
                        'forecast_time'     => $ts,
                        'wave_height'       => Arr::get($entry, 'waveHeight'),
                        'wave_period'       => Arr::get($entry, 'wavePeriod'),
                        'wave_direction'    => Arr::get($entry, 'waveDirection'),
                        'water_temperature' => Arr::get($entry, 'waterTemperature'),
                        'wind_speed'        => Arr::get($entry, 'windSpeed'),
                        'wind_direction'    => Arr::get($entry, 'windDirection'),
                        'air_temperature'   => Arr::get($entry, 'airTemperature'),
                        'updated_at'        => now(),
                        'created_at'        => now(),
                    ];
                }

                if (empty($rows)) {
                    $this->warn(PHP_EOL . "Nothing to store for {$site->name}");
                    $totals['sites_fail']++;
                    $bar->advance();
                    return;
                }

                if ($dryRun) {
                    $this->line(PHP_EOL . "Dry-run: would upsert " . count($rows) . " rows for {$site->name}");
                } else {
                    // Upsert in chunks for performance
                    $uniqueBy = ['dive_site_id', 'forecast_time'];
                    $updateCols = [
                        'wave_height','wave_period','wave_direction',
                        'water_temperature','wind_speed','wind_direction','air_temperature',
                        'updated_at'
                    ];

                    foreach (array_chunk($rows, $chunk) as $batch) {
                        ExternalConditionForecast::upsert($batch, $uniqueBy, $updateCols);
                        $totals['rows_upserted'] += count($batch);
                    }
                }

                $totals['sites_ok']++;

                // Lightweight polite delay
                if ($rateMs > 0) {
                    usleep($rateMs * 1000);
                }
            } catch (Throwable $e) {
                $this->error(PHP_EOL . "Error for {$site->name}: {$e->getMessage()}");
                $totals['sites_fail']++;
            } finally {
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Sites OK: {$totals['sites_ok']} | Sites Fail: {$totals['sites_fail']} | Upserted: {$totals['rows_upserted']} | Skipped: {$totals['rows_skipped']}");

        return Command::SUCCESS;
    }

    /**
     * Tiny retry helper.
     *
     * @template T
     * @param  int      $times
     * @param  callable $callback
     * @param  int      $sleepMs
     * @return mixed
     * @throws \Throwable
     */
    protected function retry(int $times, callable $callback, int $sleepMs = 0)
    {
        beginning:
        try {
            return $callback();
        } catch (Throwable $e) {
            if (--$times <= 0) {
                throw $e;
            }
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
            goto beginning;
        }
    }
}