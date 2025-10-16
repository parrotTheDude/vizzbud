<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dive_site_id')->nullable()->constrained()->onDelete('set null');
            $table->string('dive_site_name')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('category')->nullable(); // optional classification
            $table->text('message');
            $table->boolean('reviewed')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('reviewed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};