<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('external_condition_dayparts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dive_site_id')
                  ->constrained('dive_sites')
                  ->cascadeOnDelete();

            // local calendar date for the site’s timezone
            $table->date('local_date');

            // buckets: morning (06–11), arvo (12–16), night (17–21)
            $table->enum('part', ['morning', 'afternoon', 'night']);

            // computed status from thresholds
            $table->enum('status', ['green', 'yellow', 'red', 'unknown'])
                  ->default('unknown');

            // conservative reducers per bucket (nullable if sparse)
            $table->float('wave_height_max')->nullable();   // meters
            $table->float('wind_speed_max')->nullable();    // knots

            // When the summary was computed (UTC)
            $table->timestamp('computed_at')->useCurrent();

            $table->timestamps();

            // Fast lookups and idempotent writes
            $table->unique(['dive_site_id', 'local_date', 'part'], 'uniq_site_date_part');
            $table->index(['local_date', 'part'], 'idx_date_part');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_condition_dayparts');
    }
};