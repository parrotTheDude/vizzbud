<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryAndRegionToDiveSitesTable extends Migration
{
    public function up(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->string('country')->nullable()->after('lng');
            $table->string('region')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->dropColumn(['country', 'region']);
        });
    }
}