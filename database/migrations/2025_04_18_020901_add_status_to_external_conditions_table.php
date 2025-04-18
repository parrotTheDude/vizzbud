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
        // In the migration file:
        Schema::table('external_conditions', function (Blueprint $table) {
            $table->string('status')->nullable()->after('retrieved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_conditions', function (Blueprint $table) {
            //
        });
    }
};
