<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_condition_dayparts', function (Blueprint $table) {

            // New numeric richness
            $table->double('wave_period_max')->nullable()->after('wave_height_max');
            $table->double('swell_dir_avg')->nullable()->after('wave_period_max');
            $table->double('wind_dir_avg')->nullable()->after('swell_dir_avg');

            // Store the computed score (0–10)
            $table->double('score')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('external_condition_dayparts', function (Blueprint $table) {
            $table->dropColumn([
                'wave_period_max',
                'swell_dir_avg',
                'wind_dir_avg',
                'score',
            ]);
        });
    }
};