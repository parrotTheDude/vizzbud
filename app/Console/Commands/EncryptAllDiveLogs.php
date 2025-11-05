<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptAllDiveLogs extends Command
{
    protected $signature   = 'vizzbud:encrypt-all-dive-logs {--fields=title,notes}';
    protected $description = 'Encrypt user_dive_logs fields if not already encrypted (reads raw DB values)';

    public function handle(): int
    {
        $fields = collect(explode(',', $this->option('fields')))
            ->map(fn($f) => trim($f))
            ->filter();

        $this->info('🔐 Encrypting fields: ' . $fields->join(', '));
        $this->warn('This will encrypt any plaintext values but skip already-encrypted ones.');

        $logs = DB::table('user_dive_logs')->select('id', ...$fields)->get();
        $updated = 0;

        foreach ($logs as $log) {
            $update = [];
            foreach ($fields as $field) {
                $raw = $log->$field;

                if (empty($raw)) continue;
                if ($this->looksEncrypted($raw)) continue;

                $update[$field] = Crypt::encryptString($raw);
            }

            if ($update) {
                DB::table('user_dive_logs')->where('id', $log->id)->update($update);
                $updated++;
            }
        }

        $this->info("✅ Done. {$updated} rows updated.");
        return self::SUCCESS;
    }

    /**
     * Quick detector: skip JSON strings that already look like Laravel ciphertext
     */
    protected function looksEncrypted(string $value): bool
    {
        if (!str_starts_with($value, '{"iv":"')) return false;
        if (!str_contains($value, '"mac"')) return false;

        $decoded = json_decode($value, true);
        return isset($decoded['iv'], $decoded['value'], $decoded['mac']);
    }
}