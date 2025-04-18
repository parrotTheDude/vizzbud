<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiveSite;

class NewDiveSitesSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'name' => 'The Apartments (Long Reef)',
                'description' => 'Features large boulders, caves, and grey nurse sharks.',
                'lat' => -33.7323,
                'lng' => 151.3057,
                'max_depth' => 21,
                'avg_depth' => 15,
                'dive_type' => 'boat',
                'suitability' => 'Open Water',
            ],
            [
                'name' => 'Bluefish Point',
                'description' => 'A popular site for encountering grey nurse sharks.',
                'lat' => -33.8184,
                'lng' => 151.2978,
                'max_depth' => 16,
                'avg_depth' => 12,
                'dive_type' => 'boat',
                'suitability' => 'Open Water',
            ],
            [
                'name' => 'Clifton Gardens (Chowder Bay)',
                'description' => 'Muck dive ideal for seahorses, pipefish and octopuses.',
                'lat' => -33.8416,
                'lng' => 151.2550,
                'max_depth' => 15,
                'avg_depth' => 10,
                'dive_type' => 'shore',
                'suitability' => 'Open Water',
            ],
            [
                'name' => 'Valiant Shipwreck (Palm Beach)',
                'description' => 'An old tugboat wreck covered in coral.',
                'lat' => -33.5933,
                'lng' => 151.3215,
                'max_depth' => 27,
                'avg_depth' => 25,
                'dive_type' => 'boat',
                'suitability' => 'Advanced',
            ],
            [
                'name' => 'The Steps (Kurnell)',
                'description' => 'Famous for weedy sea dragons and macro life.',
                'lat' => -34.0189,
                'lng' => 151.2244,
                'max_depth' => 18,
                'avg_depth' => 12,
                'dive_type' => 'shore',
                'suitability' => 'Open Water',
            ],
        ];

        foreach ($sites as $site) {
            DiveSite::updateOrCreate(
                ['name' => $site['name']],
                $site
            );
        }
    }
}