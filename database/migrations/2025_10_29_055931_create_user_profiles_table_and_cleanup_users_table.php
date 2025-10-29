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
        // 1️⃣ Create the new user_profiles table
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Dive-related info
            $table->foreignId('dive_level_id')->nullable()->constrained('dive_levels')->nullOnDelete();
            $table->string('avatar_url')->nullable();
            $table->text('bio')->nullable();

            $table->timestamps();
        });

        // 2️⃣ Drop moved columns from users table
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'avatar_url')) {
                $table->dropColumn('avatar_url');
            }
            if (Schema::hasColumn('users', 'dive_level_id')) {
                $table->dropForeign(['dive_level_id']);
                $table->dropColumn('dive_level_id');
            }
            if (Schema::hasColumn('users', 'bio')) {
                $table->dropColumn('bio');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add dropped columns to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('dive_level_id')->nullable()->constrained('dive_levels')->nullOnDelete();
            $table->string('avatar_url')->nullable();
            $table->text('bio')->nullable();
        });

        // Drop the new table
        Schema::dropIfExists('user_profiles');
    }
};