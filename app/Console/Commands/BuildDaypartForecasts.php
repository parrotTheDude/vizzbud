<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use App\Models\DiveSite;
use App\Models\ExternalConditionForecast;
use Throwable;

class BuildDaypartForecasts extends Command
{
    protected $signature = 'vizzbud:build-dayparts
        {--site= : ID or slug/name of a specific site}
        {--hours=72 : Horizon to consider from now (UTC)}
        {--chunk=200 : Upsert batch size}
        {--prune=1 : Prune dayparts before today (local)}
        {--dry-run : Only compute, do not write}';

    protected $description = 'Aggregate hourly forecast data into morning/afternoon/night daypart rollups.';

    public function handle(): int
    {
        $hours  = max(1, (int) $this->option('hours'));
        $chunk  = max(10, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $prune  = (bool) $this->option('prune');
        $siteOpt= $this->option('site');

        $this->info("Building dayparts (horizon {$hours}h, chunk {$chunk}, dry-run " . ($dryRun ? 'yes':'no') . ")");

        // Fetch sites
        $sites = DiveSite::query()
            ->when($siteOpt, function ($q) use ($siteOpt) {
                if (is_numeric($siteOpt)) {
                    $q->where('id', $siteOpt);
                } else {
                    $q->where(fn($qq) => $qq->where('slug',$siteOpt)->orWhere('name',$siteOpt));
                }
            }, fn($q) => $q->where('is_active', true))
            ->select(['id','name','slug','timezone'])
            ->orderBy('id')
            ->get();

        if ($sites->isEmpty()) {
            $this->warn("No sites matched.");
            return self::SUCCESS;
        }

        $nowUtc = CarbonImmutable::now('UTC')->floorHour();
        $endUtc = $nowUtc->addHours($hours);

        // Optional prune
        if ($prune && !$dryRun) {
            $deletedTotal = 0;
            foreach ($sites as $site) {
                $tz  = $site->timezone ?: 'UTC';
                $todayLocal = $nowUtc->setTimezone($tz)->toDateString();

                $deleted = DB::table('external_condition_dayparts')
                    ->where('dive_site_id', $site->id)
                    ->where('local_date', '<', $todayLocal)
                    ->delete();

                $deletedTotal += $deleted;
            }
            $this->info("Pruned {$deletedTotal} old daypart rows.");
        }

        $bar = $this->output->createProgressBar($sites->count());
        $bar->start();

        $upserts=0; $sitesOk=0; $sitesFail=0;

        foreach ($sites as $site) {
            try {
                $tz = $site->timezone ?: 'UTC';

                // Pull hourly forecasts
                $hoursRows = ExternalConditionForecast::query()
                    ->where('dive_site_id',$site->id)
                    ->whereBetween('forecast_time', [$nowUtc, $endUtc])
                    ->orderBy('forecast_time')
                    ->get([
                        'forecast_time',
                        'wave_height',
                        'wave_period',
                        'wave_direction',
                        'wind_speed',
                        'wind_direction',
                        'score'
                    ]);

                if ($hoursRows->isEmpty()) {
                    $sitesFail++;
                    $bar->advance();
                    continue;
                }

                // Bucket hours
                $buckets = [];

                foreach ($hoursRows as $row) {
                    $local = CarbonImmutable::parse($row->forecast_time)->setTimezone($tz);
                    $hour  = (int) $local->format('G');

                    $part = $this->hourToPart($hour);
                    if (!$part) continue;

                    $key = $local->toDateString() . '|' . $part;

                    $buckets[$key]['local_date'] = $local->toDateString();
                    $buckets[$key]['part'] = $part;

                    $buckets[$key]['wave_height'][] = $row->wave_height;
                    $buckets[$key]['wave_period'][] = $row->wave_period;
                    $buckets[$key]['wave_direction'][] = $row->wave_direction;
                    $buckets[$key]['wind_speed'][] = $row->wind_speed;
                    $buckets[$key]['wind_direction'][] = $row->wind_direction;
                    $buckets[$key]['scores'][] = $row->score;
                }

                if (!$buckets) {
                    $sitesFail++;
                    $bar->advance();
                    continue;
                }

                // Build aggregated rows
                $rows = [];
                $now = now();

                foreach ($buckets as $key => $bucket) {

                    // Max values
                    $waveMax = $this->maxOrNull($bucket['wave_height']);
                    $windMax = $this->maxOrNull($bucket['wind_speed']);
                    $periodMax = $this->maxOrNull($bucket['wave_period']);

                    // Circular means for directions
                    $swellDirAvg = $this->circularMean($bucket['wave_direction']);
                    $windDirAvg  = $this->circularMean($bucket['wind_direction']);

                    // Score percentile
                    $p75 = $this->percentile($bucket['scores'], 0.75);

                    // Final status
                    $status = compute_condition_status_from_score($p75);

                    $rows[] = [
                        'dive_site_id'    => $site->id,
                        'local_date'      => $bucket['local_date'],
                        'part'            => $bucket['part'],
                        'status'          => $status,
                        'wave_height_max' => $waveMax,
                        'wind_speed_max'  => $windMax,
                        'wave_period_max' => $periodMax,
                        'swell_dir_avg'   => $swellDirAvg,
                        'wind_dir_avg'    => $windDirAvg,
                        'score'           => $p75,
                        'computed_at'     => $now,
                        'updated_at'      => $now,
                        'created_at'      => $now,
                    ];
                }

                if (!$dryRun) {
                    foreach (array_chunk($rows, $chunk) as $batch) {
                        DB::table('external_condition_dayparts')->upsert(
                            $batch,
                            ['dive_site_id','local_date','part'],
                            [
                                'status','wave_height_max','wind_speed_max',
                                'wave_period_max','swell_dir_avg','wind_dir_avg',
                                'score','computed_at','updated_at'
                            ]
                        );
                        $upserts += count($batch);
                    }
                }

                $sitesOk++;

            } catch (Throwable $e) {
                Log::error("Daypart error: {$e->getMessage()}", ['site'=>$site->id]);
                $sitesFail++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Sites OK: {$sitesOk} | Fail: {$sitesFail} | Upserted: {$upserts}");

        return self::SUCCESS;
    }

    private function hourToPart(int $hour): ?string
    {
        return match (true) {
            $hour >= 6 && $hour <= 11 => 'morning',
            $hour >= 12 && $hour <= 16 => 'afternoon',
            $hour >= 17 && $hour <= 21 => 'night',
            default => null,
        };
    }

    private function maxOrNull(array $values)
    {
        $nums = array_filter($values, fn($v)=>is_numeric($v));
        return empty($nums) ? null : max($nums);
    }

    private function percentile(array $values, float $p)
    {
        $vals = array_values(array_filter($values, fn($v)=>is_numeric($v)));
        if (!$vals) return null;

        sort($vals);
        $index = (count($vals)-1) * $p;
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower === $upper) return $vals[$lower];

        return $vals[$lower] + ($vals[$upper]-$vals[$lower]) * ($index-$lower);
    }

    private function circularMean(array $angles)
    {
        $valid = array_values(array_filter($angles, fn($a)=>is_numeric($a)));

        if (!$valid) return null;

        $sumSin = 0;
        $sumCos = 0;

        foreach ($valid as $deg) {
            $rad = deg2rad($deg);
            $sumSin += sin($rad);
            $sumCos += cos($rad);
        }

        return fmod(rad2deg(atan2($sumSin,$sumCos))+360,360);
    }
}