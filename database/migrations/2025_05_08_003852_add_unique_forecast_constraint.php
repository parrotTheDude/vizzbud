<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up()
    {
        Schema::table('external_condition_forecasts', function (Blueprint $table) {
            $table->unique(['dive_site_id', 'forecast_time'], 'unique_site_forecast');
        });
    }

    public function down()
    {
        Schema::table('external_condition_forecasts', function (Blueprint $table) {
            $table->dropUnique('unique_site_forecast');
        });
    }
};