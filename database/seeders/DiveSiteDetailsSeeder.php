<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiveSite;

class DiveSiteDetailsSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            'Camp Cove' => [
                'max_depth' => 10,
                'avg_depth' => 6,
                'dive_type' => 'shore',
                'suitability' => 'Open Water',
            ],
            'Bare Island' => [
                'max_depth' => 18,
                'avg_depth' => 12,
                'dive_type' => 'shore',
                'suitability' => 'Open Water',
            ],
            'Shelly Beach' => [
                'max_depth' => 14,
                'avg_depth' => 8,
                'dive_type' => 'shore',
                'suitability' => 'Open Water',
            ],
            'Gordon’s Bay' => [
                'max_depth' => 14,
                'avg_depth' => 10,
                'dive_type' => 'shore',
                'suitability' => 'Open Water',
            ],
            'Magic Point' => [
                'max_depth' => 25,
                'avg_depth' => 20,
                'dive_type' => 'boat',
                'suitability' => 'Advanced',
            ],
            'Oak Park' => [
                'max_depth' => 11,
                'avg_depth' => 7,
                'dive_type' => 'shore',
                'suitability' => 'Open Water',
            ],
            'Shark Point' => [
                'max_depth' => 26,
                'avg_depth' => 15,
                'dive_type' => 'shore',
                'suitability' => 'Advanced',
            ],
        ];

        foreach ($sites as $name => $details) {
            DiveSite::where('name', $name)->update($details);
        }
    }
}