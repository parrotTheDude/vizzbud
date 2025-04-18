<?php

// database/migrations/xxxx_xx_xx_create_user_dive_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDiveLogsTable extends Migration
{
    public function up()
    {
        Schema::create('user_dive_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dive_site_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('dive_date');
            $table->decimal('depth', 5, 2)->nullable();
            $table->integer('duration')->nullable(); // in minutes

            // Optional extras
            $table->string('buddy')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('air_start', 5, 1)->nullable();
            $table->decimal('air_end', 5, 1)->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->string('suit_type')->nullable();
            $table->string('tank_type')->nullable();
            $table->string('weight_used')->nullable();
            $table->decimal('visibility', 4, 1)->nullable();
            $table->integer('rating')->nullable(); // 1–5 stars maybe

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_dive_logs');
    }
}