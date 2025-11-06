<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptUserProfileBios extends Command
{
    protected $signature = 'profiles:encrypt-bios {--chunk=500} {--dry-run}';
    protected $description = 'Encrypt plain-text user_profiles.bio in place (idempotent).';

    public function handle(): int
    {
        $chunk = (int)$this->option('chunk');
        $dry   = (bool)$this->option('dry-run');

        $this->info("Scanning user_profiles.bio (chunk={$chunk}, dry-run=" . ($dry?'yes':'no') . ")");

        $total = 0; $encrypted = 0; $skipped = 0; $errors = 0;

        DB::table('user_profiles')
            ->select('id','bio','updated_at')
            ->whereNotNull('bio')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use (&$total, &$encrypted, &$skipped, &$errors, $dry) {
                foreach ($rows as $row) {
                    $total++;

                    $bio = $row->bio;

                    // Empty or whitespace-only → skip
                    if (trim($bio) === '') { $skipped++; continue; }

                    // If already encrypted, Crypt::decryptString will succeed
                    $alreadyEncrypted = false;
                    try {
                        Crypt::decryptString($bio);
                        $alreadyEncrypted = true;
                    } catch (DecryptException $e) {
                        $alreadyEncrypted = false;
                    } catch (\Throwable $e) {
                        // Unknown payload format; treat as plaintext
                        $alreadyEncrypted = false;
                    }

                    if ($alreadyEncrypted) { $skipped++; continue; }

                    try {
                        $cipher = Crypt::encryptString($bio);
                        if (!$dry) {
                            DB::table('user_profiles')
                                ->where('id', $row->id)
                                ->update([
                                    'bio' => $cipher,
                                    'updated_at' => now(),
                                ]);
                        }
                        $encrypted++;
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->error("id={$row->id} error: {$e->getMessage()}");
                    }
                }
            });

        $this->line("Total scanned: {$total}");
        $this->line("Encrypted:     {$encrypted}");
        $this->line("Skipped:       {$skipped}");
        $this->line("Errors:        {$errors}");

        return $errors ? self::FAILURE : self::SUCCESS;
    }
}