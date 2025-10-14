<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dive_site_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dive_site_id')
                  ->constrained('dive_sites')
                  ->cascadeOnDelete();

            $table->string('image_path'); // e.g. images/divesites/shellybeach.webp
            $table->string('caption')->nullable(); // optional description

            // Credit info
            $table->string('artist_name')->nullable();
            $table->string('artist_instagram')->nullable();
            $table->string('artist_website')->nullable();

            // Meta
            $table->boolean('is_featured')->default(false); // highlight one per site
            $table->unsignedSmallInteger('order')->default(0); // sort gallery
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dive_site_photos');
    }
};