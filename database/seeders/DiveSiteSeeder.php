<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiveSite;

class DiveSiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'name' => 'Bare Island',
                'lat' => -33.9930,
                'lng' => 151.2339,
                'description' => 'One of Sydney’s most popular dive sites, great for beginners and macro lovers.'
            ],
            [
                'name' => 'Shelly Beach',
                'lat' => -33.7995,
                'lng' => 151.3002,
                'description' => 'A protected marine reserve in Manly, full of marine life.'
            ],
            [
                'name' => 'Gordon’s Bay',
                'lat' => -33.9170,
                'lng' => 151.2648,
                'description' => 'Home to the underwater nature trail and calm conditions.'
            ],
            [
                'name' => 'Camp Cove',
                'lat' => -33.8484,
                'lng' => 151.2813,
                'description' => 'Shallow dive with sandy bottom, good for night dives and training.'
            ],
            [
                'name' => 'Oak Park',
                'lat' => -34.0502,
                'lng' => 151.1567,
                'description' => 'A great reef dive with swim-throughs and plenty of fish.'
            ],
            [
                'name' => 'Magic Point',
                'lat' => -33.9660,
                'lng' => 151.2639,
                'description' => 'Famous for its grey nurse sharks and dramatic underwater cliffs.'
            ],
            [
                'name' => 'Shark Point',
                'lat' => -33.9217,
                'lng' => 151.2598,
                'description' => 'Advanced dive with stunning reef and fish life.'
            ]
        ];

        foreach ($sites as $site) {
            DiveSite::create($site);
        }
    }
}