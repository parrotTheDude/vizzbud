<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateUserLoginStats
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Update quick metrics
        $user->last_login_at = now('UTC');
        $user->last_login_ip = request()->ip();

        // Increment 
        $user->login_count = ($user->login_count ?? 0) + 1;

        $user->saveQuietly();
    }
}