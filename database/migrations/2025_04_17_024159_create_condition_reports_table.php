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
        // updated migration
        Schema::create('condition_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dive_site_id')->constrained()->onDelete('cascade');
            $table->decimal('viz_rating', 3, 1)->nullable(); // numeric for heatmaps
            $table->text('comment')->nullable();
            $table->timestamp('reported_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_reports');
    }
};
