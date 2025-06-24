<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('blog_posts', function (Blueprint $table) {
        $table->string('featured_image_alt')->nullable();
    });
}

public function down(): void
{
    Schema::table('blog_posts', function (Blueprint $table) {
        $table->dropColumn('featured_image_alt');
    });
}
};
