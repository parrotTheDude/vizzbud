<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('external_condition_forecasts', function (Blueprint $table) {
            // Adjust positions as you like
            $table->decimal('score', 5, 2)->nullable()->after('air_temperature');
            $table->string('status', 20)->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('external_condition_forecasts', function (Blueprint $table) {
            $table->dropColumn(['score', 'status']);
        });
    }
};