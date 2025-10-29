<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

        // 2️⃣ Drop columns from users safely
        if (Schema::hasTable('users')) {

            // Drop foreign key manually if it exists in DB
            $fkExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'users' 
                  AND CONSTRAINT_NAME = 'users_dive_level_id_foreign'
            ");

            if (!empty($fkExists)) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropForeign(['dive_level_id']);
                });
            }

            // Drop columns if they exist
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'dive_level_id')) {
                    $table->dropColumn('dive_level_id');
                }
                if (Schema::hasColumn('users', 'avatar_url')) {
                    $table->dropColumn('avatar_url');
                }
                if (Schema::hasColumn('users', 'bio')) {
                    $table->dropColumn('bio');
                }
            });
        }
    }

    public function down(): void
    {
        // Re-add dropped columns to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'dive_level_id')) {
                $table->foreignId('dive_level_id')->nullable()->constrained('dive_levels')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable();
            }
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable();
            }
        });

        // Drop user_profiles table
        Schema::dropIfExists('user_profiles');
    }
};