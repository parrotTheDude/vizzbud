<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('external_conditions', function (Blueprint $table) {
            $table->decimal('score', 5, 2)
                  ->nullable()
                  ->after('status');
        });
    }

    public function down()
    {
        Schema::table('external_conditions', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};