<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class EncryptUserEmails extends Command
{
    protected $signature = 'users:encrypt-emails';
    protected $description = 'Encrypt all user emails and generate deterministic HMAC lookup hashes.';

    public function handle()
    {
        $pepper = config('app.email_pepper');

        if (empty($pepper)) {
            $this->error('❌ Missing APP_EMAIL_PEPPER in .env');
            return Command::FAILURE;
        }

        $count = DB::table('users')->count();
        $this->info("🔐 Encrypting {$count} user emails...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // ⚡ Bypass casts — read directly from DB
        DB::table('users')->orderBy('id')->chunk(100, function ($users) use ($pepper, $bar) {
            foreach ($users as $user) {
                $email = $user->email;

                // Skip already encrypted emails (Laravel ciphertexts start with eyJpdiI6)
                if (is_string($email) && str_starts_with($email, 'eyJpdiI6')) {
                    $bar->advance();
                    continue;
                }

                if (empty($email)) {
                    $bar->advance();
                    continue;
                }

                // Encrypt and hash
                $encrypted = Crypt::encryptString($email);
                $hash = hash_hmac('sha256', strtolower(trim($email)), $pepper);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'email' => $encrypted,
                        'email_hash' => $hash,
                        'updated_at' => now(),
                    ]);

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ All user emails encrypted & hashed successfully.');

        return Command::SUCCESS;
    }
}