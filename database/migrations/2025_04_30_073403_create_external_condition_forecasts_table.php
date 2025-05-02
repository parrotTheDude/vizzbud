<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('external_condition_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dive_site_id')->constrained()->onDelete('cascade');
            $table->dateTime('forecast_time');
            $table->float('wave_height')->nullable();
            $table->float('wave_period')->nullable();
            $table->float('wave_direction')->nullable();
            $table->float('water_temperature')->nullable();
            $table->float('wind_speed')->nullable();
            $table->float('wind_direction')->nullable();
            $table->float('air_temperature')->nullable();
            $table->timestamps();

            $table->unique(['dive_site_id', 'forecast_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_condition_forecasts');
    }
};
