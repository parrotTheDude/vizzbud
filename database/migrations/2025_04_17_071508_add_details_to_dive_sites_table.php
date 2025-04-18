<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('dive_sites', function (Blueprint $table) {
        $table->unsignedTinyInteger('max_depth')->nullable()->after('description');
        $table->unsignedTinyInteger('avg_depth')->nullable()->after('max_depth');
        $table->enum('dive_type', ['shore', 'boat'])->nullable()->after('avg_depth');
        $table->enum('suitability', ['Open Water', 'Advanced', 'Deep'])->nullable()->after('dive_type');
    });
}

public function down(): void
{
    Schema::table('dive_sites', function (Blueprint $table) {
        $table->dropColumn(['max_depth', 'avg_depth', 'dive_type', 'suitability']);
    });
}
};
