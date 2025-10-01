<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_conditions', function (Blueprint $table) {
            // Ensure no duplicates: one row per dive_site_id + retrieved_at
            $table->unique(['dive_site_id', 'retrieved_at'], 'ec_site_time_unique');
        });
    }

    public function down(): void
    {
        Schema::table('external_conditions', function (Blueprint $table) {
            $table->dropUnique('ec_site_time_unique');
        });
    }
};