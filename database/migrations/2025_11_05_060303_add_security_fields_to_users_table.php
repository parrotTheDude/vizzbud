<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 🧩 Add a UUID for public-safe IDs
            if (!Schema::hasColumn('users', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id')->unique();
            }

            // 🧱 Add account status
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'suspended', 'deleted'])
                      ->default('active')
                      ->after('role');
            }

            // 🧠 Add lightweight login summary fields
            if (!Schema::hasColumn('users', 'login_count')) {
                $table->unsignedInteger('login_count')->default(0)->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('login_count');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }

            // 🗑 Add soft deletes 
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 🧾 Backfill UUIDs for existing users
        $users = \DB::table('users')->whereNull('uuid')->get();
        foreach ($users as $user) {
            \DB::table('users')
                ->where('id', $user->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'uuid')) $table->dropColumn('uuid');
            if (Schema::hasColumn('users', 'status')) $table->dropColumn('status');
            if (Schema::hasColumn('users', 'login_count')) $table->dropColumn('login_count');
            if (Schema::hasColumn('users', 'last_login_at')) $table->dropColumn('last_login_at');
            if (Schema::hasColumn('users', 'last_login_ip')) $table->dropColumn('last_login_ip');
            if (Schema::hasColumn('users', 'deleted_at')) $table->dropSoftDeletes();
        });
    }
};