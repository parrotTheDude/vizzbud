<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('dive_sites', function (Blueprint $table) {
            // 🔗 new relationship
            $table->foreignId('region_id')
                  ->nullable()
                  ->after('id')
                  ->constrained()
                  ->nullOnDelete();

            // 🧹 remove old text fields
            $table->dropColumn(['region', 'country']);
        });
    }

    public function down(): void {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->dropConstrainedForeignId('region_id');
        });
    }
};