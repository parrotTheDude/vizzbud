<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('user_dive_logs', function (Blueprint $table) {
        $table->string('title')->nullable()->after('dive_date');
    });
}

public function down(): void
{
    Schema::table('user_dive_logs', function (Blueprint $table) {
        $table->dropColumn('title');
    });
}
};
