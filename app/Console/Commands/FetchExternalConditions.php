<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\DiveSite;
use App\Models\ExternalCondition;
use App\Services\OpenMeteoService;
use Throwable;
use Carbon\CarbonImmutable;

class FetchExternalConditions extends Command
{
    protected $signature = 'vizzbud:fetch-conditions 
        {--batch=100 : Number of sites to process per chunk}
        {--prune-days=30 : Delete rows older than X days}
        {--site= : Fetch only one dive site by ID}
        {--debug : Show scoring breakdown}';

    protected $description = 'Fetch and store latest external conditions for dive sites';

    public function handle(OpenMeteoService $weather): int
    {
        $oneSiteId = $this->option('site');

        if ($oneSiteId) {
            return $this->handleSingleSite($weather, (int)$oneSiteId);
        }

        return $this->handleAllSites($weather);
    }

    // ------------------------------------------------------------
    // 1. PROCESS A SINGLE SITE ONLY
    // ------------------------------------------------------------
    private function handleSingleSite(OpenMeteoService $weather, int $siteId): int
    {
        $site = DiveSite::active()->find($siteId);

        if (!$site) {
            $this->error("Dive site ID {$siteId} not found or inactive.");
            return self::FAILURE;
        }

        $this->info("Fetching conditions for: {$site->name}");

        $fetchTime = CarbonImmutable::now('UTC')->startOfHour();

        try {
            $data = $weather->fetchConditions((float)$site->lat, (float)$site->lng);

            if (!$data || empty($data['hours'][0])) {
                $this->error("No valid data returned for {$site->name}");
                return self::FAILURE;
            }

            $entry = $data['hours'][0];

            // API timestamp
            $apiTime = isset($entry['time'])
                ? CarbonImmutable::parse($entry['time'])->startOfHour()
                : $fetchTime;

            // Compute score & final status
            $score  = compute_condition_score(
                $entry['waveHeight']['noaa'] ?? null,
                $entry['wavePeriod']['noaa'] ?? null,
                $entry['waveDirection']['noaa'] ?? null,
                $entry['windSpeed']['noaa'] ?? null,
                $entry['windDirection']['noaa'] ?? null,
                $site->exposure_bearing ?? null
            );

            $status = compute_condition_status_from_score($score);

            ExternalCondition::updateOrCreate(
                ['dive_site_id' => $site->id, 'retrieved_at' => $apiTime],
                [
                    'status'            => $status,
                    'score'             => $score,
                    'wave_height'       => $entry['waveHeight']['noaa'] ?? null,
                    'wave_period'       => $entry['wavePeriod']['noaa'] ?? null,
                    'wave_direction'    => $entry['waveDirection']['noaa'] ?? null,
                    'water_temperature' => $entry['waterTemperature']['noaa'] ?? null,
                    'wind_speed'        => $entry['windSpeed']['noaa'] ?? null,
                    'wind_direction'    => $entry['windDirection']['noaa'] ?? null,
                    'air_temperature'   => $entry['airTemperature']['noaa'] ?? null,
                ]
            );

            $this->info("Stored score {$score} ({$status})");

            // DEBUG OUTPUT
            if ($this->option('debug')) {
                $this->explainScore(
                    $entry['waveHeight']['noaa'] ?? null,
                    $entry['wavePeriod']['noaa'] ?? null,
                    $entry['waveDirection']['noaa'] ?? null,
                    $entry['windSpeed']['noaa'] ?? null,
                    $entry['windDirection']['noaa'] ?? null,
                    $site->exposure_bearing ?? null
                );
            }

        } catch (Throwable $e) {
            Log::error("Condition fetch failed for {$site->name}", [
                'error' => $e->getMessage()
            ]);
            $this->error("Error occurred. Check logs.");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }


    // ------------------------------------------------------------
    // 2. PROCESS ALL SITES IN BATCHES
    // ------------------------------------------------------------
    private function handleAllSites(OpenMeteoService $weather): int
    {
        $this->info('Fetching current conditions for ALL active dive sites…');

        $batchSize = (int) max(10, $this->option('batch'));
        $pruneDays = (int) max(1, $this->option('prune-days'));

        $fetchTime = CarbonImmutable::now('UTC')->startOfHour();

        // PRUNE OLD DATA
        $deleted = ExternalCondition::where('retrieved_at', '<', now()->subDays($pruneDays))->delete();
        if ($deleted > 0) {
            $this->info("Pruned {$deleted} old rows.");
        }

        $totalSites = DiveSite::active()->count();
        $bar = $this->output->createProgressBar($totalSites);
        $bar->start();

        $insertBuffer = [];
        $bufferTarget = max(50, $batchSize);

        DiveSite::active()
            ->select(['id','name','lat','lng','exposure_bearing'])
            ->orderBy('id')
            ->chunkById($batchSize, function ($sites) use (
                $weather, $fetchTime, &$insertBuffer, $bufferTarget, $bar
            ) {
                foreach ($sites as $site) {
                    try {
                        $data = $weather->fetchConditions(
                            (float)$site->lat,
                            (float)$site->lng
                        );

                        if (!$data || empty($data['hours'][0])) {
                            Log::warning("No data for {$site->name}");
                            $bar->advance();
                            continue;
                        }

                        $entry = $data['hours'][0];

                        // API timestamp
                        $apiTime = isset($entry['time'])
                            ? CarbonImmutable::parse($entry['time'])->startOfHour()
                            : $fetchTime;

                        // Compute score + status
                        $score = compute_condition_score(
                            $entry['waveHeight']['noaa'] ?? null,
                            $entry['wavePeriod']['noaa'] ?? null,
                            $entry['waveDirection']['noaa'] ?? null,
                            $entry['windSpeed']['noaa'] ?? null,
                            $entry['windDirection']['noaa'] ?? null,
                            $site->exposure_bearing ?? null
                        );

                        $status = compute_condition_status_from_score($score);

                        // Build insert row
                        $insertBuffer[] = [
                            'dive_site_id'      => $site->id,
                            'retrieved_at'      => $apiTime,
                            'status'            => $status,
                            'score'             => $score,
                            'wave_height'       => $entry['waveHeight']['noaa'] ?? null,
                            'wave_period'       => $entry['wavePeriod']['noaa'] ?? null,
                            'wave_direction'    => $entry['waveDirection']['noaa'] ?? null,
                            'water_temperature' => $entry['waterTemperature']['noaa'] ?? null,
                            'wind_speed'        => $entry['windSpeed']['noaa'] ?? null,
                            'wind_direction'    => $entry['windDirection']['noaa'] ?? null,
                            'air_temperature'   => $entry['airTemperature']['noaa'] ?? null,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];

                        if (count($insertBuffer) >= $bufferTarget) {
                            $this->flushBuffer($insertBuffer);
                        }

                    } catch (Throwable $e) {
                        Log::error('Condition fetch failed', [
                            'site_id' => $site->id,
                            'site'    => $site->name,
                            'error'   => $e->getMessage(),
                        ]);
                    }

                    $bar->advance();
                }
            });

        // flush final batch
        $this->flushBuffer($insertBuffer);

        $bar->finish();
        $this->newLine(2);
        $this->info('All dive sites updated successfully.');

        return self::SUCCESS;
    }



    // ------------------------------------------------------------
    // 3. Batch upsert
    // ------------------------------------------------------------
    private function flushBuffer(array &$buffer): void
    {
        if (empty($buffer)) return;

        ExternalCondition::upsert(
            $buffer,
            ['dive_site_id', 'retrieved_at'],
            [
                'status',
                'score',
                'wave_height',
                'wave_period',
                'wave_direction',
                'water_temperature',
                'wind_speed',
                'wind_direction',
                'air_temperature',
                'updated_at',
            ]
        );

        $buffer = [];
    }

    private function explainScore(
        ?float $waveHeightM,
        ?float $wavePeriodS,
        ?float $waveDirDeg,
        ?float $windSpeedKt,
        ?float $windDirDeg,
        ?int   $exposure
    ): void {

        $this->line("");
        $this->line("=== SCORE BREAKDOWN ===");

        if ($waveHeightM === null || $windSpeedKt === null) {
            $this->line("Missing critical data → score = UNKNOWN");
            return;
        }

        // 1) Wave height score
        $wavePoints = min(4, $waveHeightM / 0.6);
        $this->line("Wave height: {$waveHeightM}m → +" . round($wavePoints,2));


        // 2) Wind speed score — matching compute_condition_score
        $windPoints = 0.0;
        if ($windSpeedKt !== null) {
            if ($windSpeedKt <= 10) {
                // 0–0.5
                $windPoints = $windSpeedKt / 20.0;
            } else {
                // 0.5 + ramp (max 4)
                $windPoints = 0.5 + min(3.5, ($windSpeedKt - 10) / 4.0);
            }
        }
        $this->line("Wind: {$windSpeedKt}kt → +" . round($windPoints,2));


        // 3) Long-period swell (0–2)
        $periodPoints = 0;
        if ($wavePeriodS !== null && $wavePeriodS >= 12) {
            $periodPoints = min(2, ($wavePeriodS - 12) / 2);
        }
        $this->line("Period: {$wavePeriodS}s → +" . round($periodPoints,2));


        // 4) Exposure adjustments (updated to match REAL logic)
        $exposureSwell = 0;
        $exposureWind  = 0;

        if ($exposure !== null) {

            // --- Swell exposure
            if ($waveDirDeg !== null) {
                $diff = cond_angle_diff($waveDirDeg, $exposure);

                if ($diff < 30) {
                    $exposureSwell = 2;
                } elseif ($diff > 110) {
                    $exposureSwell = -1;
                }

                $this->line("Swell dir {$waveDirDeg}° vs exposure {$exposure}° (diff={$diff}°) → +{$exposureSwell}");
            }

            // --- Wind exposure
            if ($windDirDeg !== null) {
                $diff = cond_angle_diff($windDirDeg, $exposure);

                if ($diff < 30) {
                    $exposureWind = 1.5;
                } elseif ($diff > 110) {
                    $exposureWind = -0.5;
                }

                $this->line("Wind dir {$windDirDeg}° vs exposure {$exposure}° (diff={$diff}°) → +{$exposureWind}");
            }
        }


        // 5) Total score
        $total = $wavePoints + $windPoints + $periodPoints + $exposureSwell + $exposureWind;
        $total = max(0, min(10, $total));

        $this->line("------------------------------------");
        $this->line("TOTAL SCORE: " . round($total,2));
        $this->line("");
    }
}