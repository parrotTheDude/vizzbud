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
        // 1️⃣ Create dive_levels table
        Schema::create('dive_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');       // e.g. "Open Water Diver"
            $table->unsignedInteger('rank')->default(0); // e.g. 1 = beginner, 5 = instructor
            $table->timestamps();
        });

        // 2️⃣ Add dive_level_id to users table
        Schema::table('users', function (Blueprint $table) {
            // Add after email for cleaner structure
            $table->foreignId('dive_level_id')
                ->nullable()
                ->after('email')
                ->constrained('dive_levels')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the foreign key and column first
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dive_level_id');
        });

        Schema::dropIfExists('dive_levels');
    }
};