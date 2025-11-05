<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_dive_logs', function (Blueprint $table) {
            $table->text('title')->nullable()->change();
            $table->longText('notes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_dive_logs', function (Blueprint $table) {
            $table->string('title', 191)->nullable()->change();
            $table->text('notes')->nullable()->change();
        });
    }
};