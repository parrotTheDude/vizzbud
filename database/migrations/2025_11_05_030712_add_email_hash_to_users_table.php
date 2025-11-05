<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1️⃣ Ensure email column can hold encrypted ciphertext
            $table->string('email', 512)->change();

            // 2️⃣ Add hash column if not present
            if (!Schema::hasColumn('users', 'email_hash')) {
                $table->char('email_hash', 64)->nullable()->after('email');
            }

            // 3️⃣ Add unique index safely (check if exists first)
            $hasIndex = collect(DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_email_hash_unique'"))->isNotEmpty();
            if (!$hasIndex) {
                $table->unique('email_hash', 'users_email_hash_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1️⃣ Safely drop unique index if it exists
            $hasIndex = collect(DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_email_hash_unique'"))->isNotEmpty();
            if ($hasIndex) {
                $table->dropUnique('users_email_hash_unique');
            }

            // 2️⃣ Drop the column if it exists
            if (Schema::hasColumn('users', 'email_hash')) {
                $table->dropColumn('email_hash');
            }

            // 3️⃣ Optionally revert the email column size
            // $table->string('email', 255)->change();
        });
    }
};