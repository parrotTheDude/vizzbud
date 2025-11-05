<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class EncryptUserNames extends Command
{
    protected $signature = 'users:encrypt-name 
                            {--dry : Do a dry run without writing changes}
                            {--chunk=1000 : Chunk size for processing}';

    protected $description = 'Encrypt plaintext names in users.name using APP_KEY without touching model casts.';

    public function handle(): int
    {
        $dry   = (bool) $this->option('dry');
        $chunk = (int) $this->option('chunk');

        $this->info('Starting users.name encryption '.($dry ? '(dry run)' : '(live)').' ...');
        $this->line('Chunk size: '.$chunk);

        $totalScanned = 0;
        $alreadyEnc   = 0;
        $updated      = 0;
        $skippedNulls = 0;

        DB::table('users')->select('id', 'name', 'updated_at')->orderBy('id')
            ->chunk($chunk, function ($rows) use (&$totalScanned, &$alreadyEnc, &$updated, &$skippedNulls, $dry) {
                foreach ($rows as $row) {
                    $totalScanned++;

                    // Null or empty: skip
                    if ($row->name === null || $row->name === '') {
                        $skippedNulls++;
                        continue;
                    }

                    // Detect if already encrypted by attempting decrypt
                    $isEncrypted = false;
                    try {
                        // If this succeeds without exception, it was already encrypted
                        Crypt::decryptString($row->name);
                        $isEncrypted = true;
                    } catch (\Throwable $e) {
                        $isEncrypted = false;
                    }

                    if ($isEncrypted) {
                        $alreadyEnc++;
                        continue;
                    }

                    // Encrypt plaintext safely
                    $cipher = Crypt::encryptString($row->name);

                    if (!$dry) {
                        DB::table('users')->where('id', $row->id)->update([
                            'name'       => $cipher,
                            'updated_at' => now(),
                        ]);
                    }

                    $updated++;
                }
            });

        $this->newLine();
        $this->info('Done.');
        $this->line("Scanned:       {$totalScanned}");
        $this->line("Already enc:   {$alreadyEnc}");
        $this->line("Updated (enc): {$updated}");
        $this->line("Skipped nulls: {$skippedNulls}");
        $this->line($dry ? 'Dry run completed — no writes were made.' : 'Live run completed — names encrypted.');

        return self::SUCCESS;
    }
}