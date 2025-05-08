<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class DeleteUnverifiedUsers extends Command
{
    protected $signature = 'users:cleanup-unverified';
    protected $description = 'Delete users who have not verified their email after 30 days';

    public function handle()
    {
        $deleted = User::whereNull('email_verified_at')
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->delete();

        $this->info("Deleted $deleted unverified user(s).");
    }
}