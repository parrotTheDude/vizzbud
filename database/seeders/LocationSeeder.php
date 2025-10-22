<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // 🌍 Countries
        $countries = [
            ['id' => 1, 'name' => 'Australia'],
            ['id' => 2, 'name' => 'Fiji'],
            ['id' => 3, 'name' => 'Thailand'],
            ['id' => 4, 'name' => 'Indonesia'],
        ];

        foreach ($countries as $country) {
            DB::table('countries')->updateOrInsert(
                ['id' => $country['id']],
                [
                    'name' => $country['name'],
                    'slug' => Str::slug($country['name']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 🏙️ States
        $states = [
            ['id' => 1, 'name' => 'New South Wales', 'country_id' => 1],
            ['id' => 2, 'name' => 'Queensland', 'country_id' => 1],
            ['id' => 3, 'name' => 'Gold Coast', 'country_id' => 1],
            ['id' => 4, 'name' => 'Western Division', 'country_id' => 2],
            ['id' => 5, 'name' => 'Surat Thani', 'country_id' => 3],
            ['id' => 6, 'name' => 'Krabi', 'country_id' => 3],
            ['id' => 7, 'name' => 'West Nusa Tenggara', 'country_id' => 4],
        ];

        foreach ($states as $state) {
            DB::table('states')->updateOrInsert(
                ['id' => $state['id']],
                [
                    'name' => $state['name'],
                    'slug' => Str::slug($state['name']),
                    'country_id' => $state['country_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 📍 Regions
        $regions = [
            ['id' => 1, 'name' => 'Sydney', 'state_id' => 1],
            ['id' => 2, 'name' => 'Byron Bay', 'state_id' => 1],
            ['id' => 3, 'name' => 'Tweed Coast', 'state_id' => 1],
            ['id' => 4, 'name' => 'Sunshine Coast', 'state_id' => 2],
            ['id' => 5, 'name' => 'Cairns', 'state_id' => 2],
            ['id' => 6, 'name' => 'Gold Coast', 'state_id' => 3],
            ['id' => 7, 'name' => 'Yasawa Islands', 'state_id' => 4],
            ['id' => 8, 'name' => 'Koh Tao', 'state_id' => 5],
            ['id' => 9, 'name' => 'Phi Phi Islands', 'state_id' => 6],
            ['id' => 10, 'name' => 'Gili Islands', 'state_id' => 7],
        ];

        foreach ($regions as $region) {
            DB::table('regions')->updateOrInsert(
                ['id' => $region['id']],
                [
                    'name' => $region['name'],
                    'slug' => Str::slug($region['name']),
                    'state_id' => $region['state_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 🌊 Default all dive sites to Australia / NSW / Sydney
        if (DB::table('dive_sites')->count() > 0) {
            DB::table('dive_sites')->update([
                'region_id' => 1,
                'state_id' => 1,
                'country_id' => 1,
                'updated_at' => now(),
            ]);

            $this->command->info('✅ All dive sites set to region_id=1 (Sydney), state_id=1, country_id=1.');
        } else {
            $this->command->info('ℹ️ No dive sites found — skipping update.');
        }

        $this->command->info('✅ Countries, States, and Regions seeded successfully!');
    }
}