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
        {--hours=96 : How many hours ahead to store}
        {--chunk=100 : Upsert batch size}
        {--rate=0 : Sleep ms between requests}
        {--dry-run : Fetch but do not write}
        {--prune=1 : Prune out-of-window rows}';

    protected $description = 'Fetch and store forecasted dive conditions using Open-Meteo + Vizzbud scoring';

    public function handle(OpenMeteoService $weather)
    {
        $hours       = max(1, (int)$this->option('hours'));
        $chunk       = max(10, (int)$this->option('chunk'));
        $rateMs      = max(0, (int)$this->option('rate'));
        $dryRun      = (bool)$this->option('dry-run');
        $shouldPrune = (bool)$this->option('prune');
        $siteOpt     = $this->option('site');

        $this->info("Forecast fetch → {$hours}h horizon");

        // Build site query
        $sitesQuery = DiveSite::query()
            ->when($siteOpt, function ($q) use ($siteOpt) {
                if (is_numeric($siteOpt)) {
                    return $q->where('id', (int)$siteOpt);
                }
                return $q->where(fn($qq) =>
                    $qq->where('slug', $siteOpt)
                       ->orWhere('name', $siteOpt)
                );
            }, function ($q) {
                return $q->where('is_active', true);
            })
            ->select(['id','name','lat','lng','exposure_bearing'])
            ->orderBy('id');

        $totalSites = (clone $sitesQuery)->count();
        if ($totalSites === 0) {
            $this->warn("No sites found");
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalSites);
        $bar->start();

        $nowUtc    = CarbonImmutable::now('UTC')->floorHour();
        $windowEnd = $nowUtc->addHours($hours);

        // Global prune (past rows)
        if (!$dryRun && $shouldPrune) {
            $deleted = ExternalConditionForecast::where('forecast_time', '<', $nowUtc)->delete();
            $this->line("\nPruned old rows: {$deleted}");
        }

        $stats = ['sites_ok'=>0,'sites_fail'=>0,'rows_upserted'=>0,'rows_skipped'=>0,'rows_pruned'=>0];

        // Iterate all sites
        $sitesQuery->cursor()->each(function (DiveSite $site) use (
            $weather,$hours,$chunk,$rateMs,$dryRun,$nowUtc,$windowEnd,$bar,&$stats
        ) {

            try {
                if (!is_numeric($site->lat) || !is_numeric($site->lng)) {
                    $this->warn("\nSkipping {$site->name} – invalid coords");
                    $stats['sites_fail']++;
                    $bar->advance();
                    return;
                }

                // Fetch forecast
                $forecast = $this->retry(3, fn() =>
                    $weather->fetchForecasts((float)$site->lat, (float)$site->lng),
                    200
                );

                if (!$forecast || empty($forecast['hours'])) {
                    $this->warn("\nNo forecast for {$site->name}");
                    $stats['sites_fail']++;
                    $bar->advance();
                    return;
                }

                // Slice from now → hours ahead
                $rows = $this->buildForecastRows($forecast['hours'], $hours, $site);

                if (empty($rows)) {
                    $this->warn("\n{$site->name}: no rows to write");
                    $stats['sites_fail']++;
                    $bar->advance();
                    return;
                }

                // Upsert
                if ($dryRun) {
                    $this->line("\nDry-run: {$site->name} would upsert " . count($rows) . " rows");
                } else {
                    DB::transaction(function () use ($site, $rows, $chunk, $nowUtc, $windowEnd, &$stats) {

                        $unique = ['dive_site_id','forecast_time'];
                        $update = [
                            'score','status',
                            'wave_height','wave_period','wave_direction',
                            'water_temperature','wind_speed','wind_direction','air_temperature',
                            'updated_at'
                        ];

                        foreach (array_chunk($rows, $chunk) as $batch) {
                            ExternalConditionForecast::upsert($batch, $unique, $update);
                            $stats['rows_upserted'] += count($batch);
                        }

                        // Per-site pruning
                        $prunedFuture = ExternalConditionForecast::where('dive_site_id',$site->id)
                            ->where('forecast_time','>=',$windowEnd)
                            ->delete();

                        $prunedPast = ExternalConditionForecast::where('dive_site_id',$site->id)
                            ->where('forecast_time','<',$nowUtc)
                            ->delete();

                        $stats['rows_pruned'] += ($prunedFuture + $prunedPast);
                    });
                }

                $stats['sites_ok']++;

                if ($rateMs > 0) usleep($rateMs * 1000);

            } catch (Throwable $e) {
                $this->error("\nError {$site->name}: " . $e->getMessage());
                $stats['sites_fail']++;
            } finally {
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Forecast complete");
        $this->info("OK: {$stats['sites_ok']}  Fail: {$stats['sites_fail']}  Upserted: {$stats['rows_upserted']}  Skipped: {$stats['rows_skipped']}  Pruned: {$stats['rows_pruned']}");

        return self::SUCCESS;
    }

    /**
     * Convert Open-Meteo hours[] into database rows with Vizzbud scoring.
     */
    private function buildForecastRows(array $hours, int $limit, DiveSite $site): array
    {
        $nowIso = CarbonImmutable::now('UTC')->floorHour()->toIso8601String();
        $start  = 0;

        foreach ($hours as $i => $row) {
            $t = $row['time'] ?? null;
            if ($t && strtotime($t) >= strtotime($nowIso)) {
                $start = $i;
                break;
            }
        }

        $slice = array_slice($hours, $start, $limit);

        $rows = [];

        foreach ($slice as $entry) {
            $t = $entry['time'] ?? null;
            if (!$t) continue;

            $ts = CarbonImmutable::parse($t)->utc()->floorHour()->toDateTimeString();

            // Extract float values
            $waveH = $entry['waveHeight']    ?? null;
            $waveP = $entry['wavePeriod']    ?? null;
            $waveD = $entry['waveDirection'] ?? null;
            $windS = $entry['windSpeed']     ?? null;
            $windD = $entry['windDirection'] ?? null;

            // Compute Vizzbud score
            $score = compute_condition_score(
                is_numeric($waveH) ? (float)$waveH : null,
                is_numeric($waveP) ? (float)$waveP : null,
                is_numeric($waveD) ? (float)$waveD : null,
                is_numeric($windS) ? (float)$windS : null,
                is_numeric($windD) ? (float)$windD : null,
                $site->exposure_bearing
            );

            $status = compute_condition_status_from_score($score);

            $rows[] = [
                'dive_site_id'      => $site->id,
                'forecast_time'     => $ts,
                'score'             => $score >= 0 ? $score : null,
                'status'            => $status,
                'wave_height'       => $waveH,
                'wave_period'       => $waveP,
                'wave_direction'    => $waveD,
                'water_temperature' => $entry['waterTemperature'] ?? null,
                'wind_speed'        => $windS,
                'wind_direction'    => $windD,
                'air_temperature'   => $entry['airTemperature'] ?? null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        return $rows;
    }

    /**
     * Simple retry wrapper.
     */
    private function retry(int $times, callable $cb, int $sleepMs)
    {
        attempt:
        try {
            return $cb();
        } catch (Throwable $e) {
            if (--$times <= 0) throw $e;
            usleep($sleepMs * 1000);
            goto attempt;
        }
    }
}