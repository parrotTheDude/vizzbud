<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiveLevelSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('dive_levels')->insert([
            ['name' => 'Open Water Diver', 'rank' => 1],
            ['name' => 'Advanced Open Water Diver', 'rank' => 2],
            ['name' => 'Rescue Diver', 'rank' => 3],
            ['name' => 'Divemaster', 'rank' => 4],
            ['name' => 'Instructor', 'rank' => 5],
        ]);
    }
}