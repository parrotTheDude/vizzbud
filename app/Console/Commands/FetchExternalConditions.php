<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\DiveSite;
use App\Models\ExternalCondition;
use App\Services\OpenMeteoService;
use Throwable;
use Carbon\Carbon;

class FetchExternalConditions extends Command
{
    protected $signature = 'vizzbud:fetch-conditions {--batch=100}';
    protected $description = 'Fetch and store external conditions for each dive site';

    public function handle(OpenMeteoService $weather): int
    {
        $this->info('Fetching conditions for dive sites…');

        $batchSize = (int) $this->option('batch') ?: 100;
        $total = DiveSite::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $insertBuffer = [];
        $bufferTarget = 100; // DB insert batch size

        $now = now();
        // Align to hour so we can enforce uniqueness per site per hour
        $retrievedAt = $now->copy()->minute(0)->second(0);

        // TIP: add a unique index on (dive_site_id, retrieved_at)
        // Schema::table('external_conditions', fn($t) => $t->unique(['dive_site_id','retrieved_at']));

        DiveSite::query()
            ->select(['id','name','lat','lng'])
            ->orderBy('id')
            ->chunkById($batchSize, function ($sites) use (&$insertBuffer, $bufferTarget, $retrievedAt, $weather, $bar) {

                foreach ($sites as $site) {
                    try {
                        $data = retry(3, function () use ($weather, $site) {
                            // Cast to float to avoid string surprises
                            return $weather->fetchConditions((float)$site->lat, (float)$site->lng);
                        }, 200); // 200ms backoff between retries

                        if (!$data || empty($data['hours'][0])) {
                            $this->warn("No data for {$site->name}");
                            $bar->advance();
                            continue;
                        }

                        $h0 = $data['hours'][0];

                        $insertBuffer[] = [
                            'dive_site_id'      => $site->id,
                            'retrieved_at'      => $retrievedAt,  // aligned hour
                            'status'            => $data['status'] ?? null,
                            'wave_height'       => $h0['waveHeight']['noaa'] ?? null,
                            'wave_period'       => $h0['wavePeriod']['noaa'] ?? null,
                            'wave_direction'    => $h0['waveDirection']['noaa'] ?? null,
                            'water_temperature' => $h0['waterTemperature']['noaa'] ?? null,
                            'wind_speed'        => $h0['windSpeed']['noaa'] ?? null,
                            'wind_direction'    => $h0['windDirection']['noaa'] ?? null,
                            'air_temperature'   => $h0['airTemperature']['noaa'] ?? null,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];

                        if (count($insertBuffer) >= $bufferTarget) {
                            $this->flushBuffer($insertBuffer);
                        }
                    } catch (Throwable $e) {
                        // Don’t kill the loop on one bad site/API call
                        Log::warning('Condition fetch failed', [
                            'site_id' => $site->id,
                            'site'    => $site->name,
                            'error'   => $e->getMessage(),
                        ]);
                    } finally {
                        $bar->advance();
                    }
                }
            });

        // Flush any remaining rows
        $this->flushBuffer($insertBuffer);

        $bar->finish();
        $this->newLine(2);
        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Insert-or-update rows with a single query using upsert.
     * Requires a unique index on (dive_site_id, retrieved_at).
     */
    private function flushBuffer(array &$buffer): void
    {
        if (empty($buffer)) {
            return;
        }

        // Upsert ensures idempotency across repeated runs in the same hour
        ExternalCondition::upsert(
            $buffer,
            ['dive_site_id', 'retrieved_at'], // unique-by
            [
                'status',
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
}