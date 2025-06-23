<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    // Step 1: Add slug column if it doesn't exist (but NOT unique yet)
    if (!Schema::hasColumn('dive_sites', 'slug')) {
        Schema::table('dive_sites', function (Blueprint $table) {
            $table->string('slug')->after('name')->nullable(); // don't make it unique yet
        });
    }

    // Step 2: Backfill slugs for existing rows
    \App\Models\DiveSite::whereNull('slug')->orWhere('slug', '')->get()->each(function ($site) {
        $baseSlug = \Illuminate\Support\Str::slug($site->name ?? 'site-' . $site->id);
        $slug = $baseSlug;
        $counter = 1;

        while (
            \App\Models\DiveSite::where('slug', $slug)
                ->where('id', '!=', $site->id)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $site->slug = $slug;
        $site->save();
    });

    // Step 3: Add the unique index
    Schema::table('dive_sites', function (Blueprint $table) {
        $table->unique('slug');
    });
}

    /**
     * Reverse the migrations.
     */
public function down(): void
{
    Schema::table('dive_sites', function (Blueprint $table) {
        $table->dropUnique(['slug']);
        $table->dropColumn('slug');
    });
}
};
