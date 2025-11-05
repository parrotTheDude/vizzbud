<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fill any null UUIDs before making the column non-nullable
        DB::statement('UPDATE users SET uuid = UUID() WHERE uuid IS NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->char('uuid', 36)
                ->default(DB::raw('(UUID())'))
                ->change();
        });
    }

    public function down(): void
    {
        // Revert to nullable, no default
        Schema::table('users', function (Blueprint $table) {
            $table->char('uuid', 36)
                ->nullable()
                ->default(null)
                ->change();
        });
    }
};