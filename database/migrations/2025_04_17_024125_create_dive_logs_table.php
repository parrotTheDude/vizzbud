<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dive_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('dive_site_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->decimal('depth', 5, 1)->nullable();
            $table->decimal('temp', 5, 2)->nullable();
            $table->string('viz')->nullable(); // e.g. "good", "poor", "10m"
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dive_logs');
    }
};
