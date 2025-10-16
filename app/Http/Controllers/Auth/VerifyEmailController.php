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
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            log_activity('email_already_verified', $user, [
                'ip' => $request->ip(),
                'agent' => substr($request->userAgent(), 0, 255),
            ]);

            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            log_activity('email_verified', $user, [
                'method' => 'EmailVerificationRequest',
                'ip' => $request->ip(),
                'agent' => substr($request->userAgent(), 0, 255),
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }

    /**
     * Handle verification via token link (custom flow).
     */
    public function verify(Request $request, $token)
    {
        $record = VerificationToken::where('token', $token)->first();

        if (! $record || $record->isExpired()) {
            log_activity('email_verification_failed', null, [
                'token' => $token,
                'reason' => 'invalid_or_expired',
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')->withErrors(['email' => 'Invalid or expired verification link.']);
        }

        $user = $record->user;
        $user->markEmailAsVerified();

        $record->delete();

        log_activity('email_verified', $user, [
            'method' => 'token_link',
            'token_used' => $token,
            'ip' => $request->ip(),
        ]);

        return redirect('/logbook')->with('verified', true);
    }
}