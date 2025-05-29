<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasColumn('dive_sites', 'slug')) {
            Schema::table('dive_sites', function (Blueprint $table) {
                $table->string('slug')->after('name')->unique();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            //
        });
    }
};
