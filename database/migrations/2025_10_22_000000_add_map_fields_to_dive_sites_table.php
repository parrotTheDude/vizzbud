<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->string('map_image_path')->nullable()->after('marine_life');
            $table->string('map_caption')->nullable()->after('map_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->dropColumn(['map_image_path', 'map_caption']);
        });
    }
};