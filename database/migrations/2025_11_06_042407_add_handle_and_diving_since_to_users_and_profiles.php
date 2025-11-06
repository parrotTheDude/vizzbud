<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: add nullable handle
        Schema::table('users', function (Blueprint $table) {
            $table->string('handle', 50)->nullable()->after('uuid');
        });

        // Step 2: populate unique fallback handles
        DB::table('users')->get()->each(function ($user) {
            $handle = 'user' . $user->id;
            DB::table('users')->where('id', $user->id)->update(['handle' => $handle]);
        });

        // Step 3: enforce uniqueness
        Schema::table('users', function (Blueprint $table) {
            $table->unique('handle');
        });

        // Add diving_since to profiles
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->year('diving_since')->nullable()->after('dive_level_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('handle');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('diving_since');
        });
    }
};