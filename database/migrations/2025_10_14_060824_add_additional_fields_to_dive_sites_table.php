<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->text('hazards')->nullable()->after('suitability');
            $table->text('pro_tips')->nullable()->after('hazards');
            $table->text('entry_notes')->nullable()->after('pro_tips');
            $table->text('parking_notes')->nullable()->after('entry_notes');
            $table->text('marine_life')->nullable()->after('parking_notes');
        });
    }

    public function down(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->dropColumn(['hazards', 'pro_tips', 'entry_notes', 'parking_notes', 'marine_life']);
        });
    }
};