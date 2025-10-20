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
            // Add admin management columns
            $table->boolean('is_active')
                  ->default(true)
                  ->after('marine_life')
                  ->comment('Marks if the dive site is visible/active on the map');

            $table->boolean('needs_review')
                  ->default(false)
                  ->after('is_active')
                  ->comment('Flag for admins to review data or verify location');

            $table->timestamp('last_condition_sync_at')
                  ->nullable()
                  ->after('needs_review')
                  ->comment('When conditions were last synced from Open-Meteo');

            $table->string('source')
                  ->nullable()
                  ->after('last_condition_sync_at')
                  ->comment('How the site was created: manual, import, or user-submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'needs_review',
                'last_condition_sync_at',
                'source',
            ]);
        });
    }
};