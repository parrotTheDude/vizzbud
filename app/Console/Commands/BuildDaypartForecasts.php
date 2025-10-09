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
        {--prune=1 : Prune dayparts before today (local) (1=yes, 0=no)}
        {--dry-run : Compute but do not write}';

    protected $description = 'Summarize hourly forecasts into morning/afternoon/night rollups per site-day';

    // Mirror thresholds used by OpenMeteoService (read from env with same keys)
    private float $greenMaxWaveM;
    private float $greenMaxWindKt;
    private float $yellowMaxWaveM;
    private float $yellowMaxWindKt;

    public function __construct()
    {
        parent::__construct();
        $this->greenMaxWaveM   = (float) env('VIZZBUD_GREEN_MAX_WAVE_M',  1.2);
        $this->greenMaxWindKt  = (float) env('VIZZBUD_GREEN_MAX_WIND_KT', 12.0);
        $this->yellowMaxWaveM  = (float) env('VIZZBUD_YELLOW_MAX_WAVE_M', 1.8);
        $this->yellowMaxWindKt = (float) env('VIZZBUD_YELLOW_MAX_WIND_KT',18.0);
    }

    public function handle(): int
    {
        $hours   = max(1, (int) $this->option('hours'));
        $chunk   = max(10, (int) $this->option('chunk'));
        $dryRun  = (bool) $this->option('dry-run');
        $prune   = (bool) $this->option('prune');
        $siteOpt = $this->option('site');

        $this->info("Building daypart rollups (horizon: {$hours}h, chunk: {$chunk}, dry-run: " . ($dryRun ? 'yes' : 'no') . ")");

        $sites = DiveSite::query()
            ->when($siteOpt, function ($q) use ($siteOpt) {
                if (is_numeric($siteOpt)) {
                    $q->where('id', (int) $siteOpt);
                } else {
                    $q->where(fn($qq) => $qq->where('slug', $siteOpt)->orWhere('name', $siteOpt));
                }
            })
            ->select(['id','name','slug','lat','lng','timezone'])
            ->orderBy('id')
            ->get();

        if ($sites->isEmpty()) {
            $this->warn('No sites matched.');
            return self::SUCCESS;
        }

        $nowUtc = CarbonImmutable::now('UTC')->floorHour();
        $endUtc = $nowUtc->addHours($hours);

        // Optional prune (remove any rollups strictly before "today" in each site’s local date)
        if ($prune && !$dryRun) {
            $deletedTotal = 0;
            foreach ($sites as $site) {
                $tz = $site->timezone ?: 'UTC';
                $todayLocal = $nowUtc->setTimezone($tz)->toDateString();
                $deleted = DB::table('external_condition_dayparts')
                    ->where('dive_site_id', $site->id)
                    ->where('local_date', '<', $todayLocal)
                    ->delete();
                $deletedTotal += $deleted;
            }
            $this->line("Pruned {$deletedTotal} old daypart rows.");
        }

        $bar = $this->output->createProgressBar($sites->count());
        $bar->start();

        $upserts = 0;
        $sitesOk = 0;
        $sitesFail = 0;

        foreach ($sites as $site) {
            try {
                if (!is_numeric($site->lat) || !is_numeric($site->lng)) {
                    $this->warn(PHP_EOL . "Skipping {$site->name}: invalid coordinates.");
                    $sitesFail++;
                    $bar->advance();
                    continue;
                }

                $tz = $site->timezone ?: 'UTC';

                // Pull the needed horizon of hourly forecasts (UTC in DB)
                $hoursRows = ExternalConditionForecast::query()
                    ->where('dive_site_id', $site->id)
                    ->whereBetween('forecast_time', [$nowUtc->toDateTimeString(), $endUtc->toDateTimeString()])
                    ->orderBy('forecast_time')
                    ->get(['forecast_time','wave_height','wind_speed']);

                if ($hoursRows->isEmpty()) {
                    $this->line(PHP_EOL . "No hourly forecasts for {$site->name} in horizon.");
                    $sitesFail++;
                    $bar->advance();
                    continue;
                }

                // Bucket -> local_date + part
                $buckets = []; // key: "{$localDate}|{$part}" => ['waves'=>[], 'winds'=>[]]
                foreach ($hoursRows as $row) {
                    $local = CarbonImmutable::parse($row->forecast_time, 'UTC')->setTimezone($tz);
                    $hour  = (int) $local->format('G'); // 0-23

                    $part = $this->hourToPart($hour);
                    if ($part === null) {
                        continue; // skip hours outside 06–21
                    }

                    $key = $local->toDateString() . '|' . $part;

                    if (!isset($buckets[$key])) {
                        $buckets[$key] = [
                            'waves' => [],
                            'winds' => [],
                            'local_date' => $local->toDateString(),
                            'part' => $part,
                        ];
                    }

                    // Only push numeric values
                    if (is_numeric($row->wave_height)) {
                        $buckets[$key]['waves'][] = (float) $row->wave_height;
                    }
                    if (is_numeric($row->wind_speed)) {
                        $buckets[$key]['winds'][] = (float) $row->wind_speed; // already knots in your pipeline
                    }
                }

                if (empty($buckets)) {
                    $this->line(PHP_EOL . "No bucketable hours for {$site->name}.");
                    $sitesFail++;
                    $bar->advance();
                    continue;
                }

                // Build rows
                $now = CarbonImmutable::now('UTC')->toDateTimeString();
                $rows = [];
                foreach ($buckets as $b) {
                    $waveMax = empty($b['waves']) ? null : max($b['waves']);
                    $windMax = empty($b['winds']) ? null : max($b['winds']);
                    $status  = $this->computeStatus($waveMax, $windMax);

                    $rows[] = [
                        'dive_site_id'   => $site->id,
                        'local_date'     => $b['local_date'],
                        'part'           => $b['part'], // morning|afternoon|night
                        'status'         => $status,
                        'wave_height_max'=> $waveMax,
                        'wind_speed_max' => $windMax,
                        'computed_at'    => $now,
                        'updated_at'     => $now,
                        'created_at'     => $now,
                    ];
                }

                if ($dryRun) {
                    $this->line(PHP_EOL . "Dry-run: would upsert " . count($rows) . " dayparts for {$site->name}");
                } else {
                    foreach (array_chunk($rows, $chunk) as $batch) {
                        DB::table('external_condition_dayparts')->upsert(
                            $batch,
                            ['dive_site_id', 'local_date', 'part'],
                            ['status', 'wave_height_max', 'wind_speed_max', 'computed_at', 'updated_at']
                        );
                        $upserts += count($batch);
                    }
                }

                $sitesOk++;
            } catch (Throwable $e) {
                $this->error(PHP_EOL . "Error building dayparts for {$site->name}: {$e->getMessage()}");
                Log::warning('BuildDaypartForecasts error', ['site_id' => $site->id, 'err' => $e->getMessage()]);
                $sitesFail++;
            } finally {
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Sites OK: {$sitesOk} | Sites Fail: {$sitesFail} | Dayparts upserted: {$upserts}");

        return self::SUCCESS;
    }

    /** Map a 24h local hour to a daypart bucket. */
    private function hourToPart(int $hour): ?string
    {
        // morning 06–11
        if ($hour >= 6 && $hour <= 11) return 'morning';
        // afternoon 12–16
        if ($hour >= 12 && $hour <= 16) return 'afternoon';
        // night 17–21
        if ($hour >= 17 && $hour <= 21) return 'night';

        // everything else (late night 22–05) => no rollup
        return null;
    }

    /** Same logic as service; returns green/yellow/red/unknown */
    private function computeStatus(?float $waveM, ?float $windKt): string
    {
        if ($waveM === null || $windKt === null) {
            return 'unknown'; // don’t penalize missing bucket, you can choose 'red' if you prefer
        }
        if ($waveM < $this->greenMaxWaveM && $windKt < $this->greenMaxWindKt) {
            return 'green';
        }
        if ($waveM < $this->yellowMaxWaveM && $windKt < $this->yellowMaxWindKt) {
            return 'yellow';
        }
        return 'red';
    }
}