<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();
        $timestamp = now('UTC')->toDateTimeString();

        if ($user->hasVerifiedEmail()) {
            // ✅ Already verified — log and redirect
            log_activity('email_verification_prompt_skipped_verified', $user, [
            ]);

            return redirect()->intended(route('logbook.index'));
        }

        // 🕒 Still unverified — log prompt display
        log_activity('email_verification_prompt_shown', $user, [
        ]);

        return view('auth.verify-email');
    }
}