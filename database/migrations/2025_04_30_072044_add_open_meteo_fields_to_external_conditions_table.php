<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('external_conditions', function (Blueprint $table) {
            $table->float('wave_height')->nullable();
            $table->float('wave_period')->nullable();
            $table->float('wave_direction')->nullable();
            $table->float('water_temperature')->nullable();
            $table->float('wind_speed')->nullable();
            $table->float('wind_direction')->nullable();
            $table->float('air_temperature')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('external_conditions', function (Blueprint $table) {
            $table->dropColumn([
                'wave_height', 'wave_period', 'wave_direction',
                'water_temperature', 'wind_speed', 'wind_direction',
                'air_temperature'
            ]);
        });
    }
};
