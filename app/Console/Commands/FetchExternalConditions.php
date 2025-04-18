<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DiveSite;
use App\Models\ExternalCondition;
use App\Services\OpenMeteoService;
use Carbon\Carbon;

class FetchExternalConditions extends Command
{
    protected $signature = 'vizzbud:fetch-conditions';
    protected $description = 'Fetch and store external conditions for each dive site';

    public function handle(OpenMeteoService $weather)
    {
        $this->info('Fetching conditions...');

        DiveSite::all()->each(function ($site) use ($weather) {
            $data = $weather->fetchConditions((float) $site->lat, (float) $site->lng);
            $status = $data['status'] ?? null;

            if ($data) {
                ExternalCondition::create([
                    'dive_site_id' => $site->id,
                    'data' => $data,
                    'status' => $status,
                    'retrieved_at' => now(),
                ]);

                $this->info("Stored conditions for {$site->name}");
            } else {
                $this->warn("Failed to fetch for {$site->name}");
            }
        });
    }
}