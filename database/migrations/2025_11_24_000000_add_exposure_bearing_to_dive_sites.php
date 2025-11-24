<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->unsignedSmallInteger('exposure_bearing')
                ->nullable()
                ->after('lng')
                ->comment('Worst exposure bearing in degrees (0–359). Swell/wind FROM this direction is most exposed.');
        });
    }

    public function down(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->dropColumn('exposure_bearing');
        });
    }
};