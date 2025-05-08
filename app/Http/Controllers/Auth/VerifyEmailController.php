<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\VerificationToken;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }

    public function verify(Request $request, $token)
    {
        $record = VerificationToken::where('token', $token)->first();

        if (! $record || $record->isExpired()) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid or expired verification link.']);
        }

        $user = $record->user;
        $user->markEmailAsVerified(); // assumes this method sets `email_verified_at`

        $record->delete();

        return redirect('/logbook')->with('verified', true);
    }
}
