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
        Schema::create('external_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dive_site_id')->constrained()->onDelete('cascade');
            $table->json('data'); // all API response fields
            $table->timestamp('retrieved_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_conditions');
    }
};
