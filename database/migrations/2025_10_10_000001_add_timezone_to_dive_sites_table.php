<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            // IANA tz like "Australia/Sydney"
            $table->string('timezone', 64)->nullable()->after('lng')->index();
        });
    }

    public function down(): void
    {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->dropIndex(['timezone']);
            $table->dropColumn('timezone');
        });
    }
};