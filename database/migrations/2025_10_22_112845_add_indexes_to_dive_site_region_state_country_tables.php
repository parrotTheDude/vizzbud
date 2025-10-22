<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 🐠 Dive Sites
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->index(['lat', 'lng']);    // for geo & nearby searches
            $table->index('region_id');       // for region lookups
        });

        // 📍 Regions
        Schema::table('regions', function (Blueprint $table) {
            $table->index('state_id');        // join to states
            $table->index('name');            // search by region name
        });

        // 🗺 States
        Schema::table('states', function (Blueprint $table) {
            $table->index('country_id');      // join to countries
            $table->index('name');            // search by state name
        });

        // 🌏 Countries
        Schema::table('countries', function (Blueprint $table) {
            $table->index('name');            // search by country name
        });
    }

    public function down(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->dropIndex(['lat', 'lng']);
            $table->dropIndex(['region_id']);
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->dropIndex(['state_id']);
            $table->dropIndex(['name']);
        });

        Schema::table('states', function (Blueprint $table) {
            $table->dropIndex(['country_id']);
            $table->dropIndex(['name']);
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};