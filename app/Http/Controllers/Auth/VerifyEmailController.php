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
     * Handle verification for logged-in users (Laravel default).
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $email = $user->email ? normalize_email($user->email) : null;

        // 🧾 Already verified
        if ($user->hasVerifiedEmail()) {
            log_activity('email_already_verified', $user, [
                'email' => $email,
            ]);

            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        // ✅ Freshly verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            log_activity('email_verified', $user, [
                'email'  => $email,
                'method' => 'EmailVerificationRequest',
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }

    /**
     * Handle verification via custom token link (email link).
     */
    public function verify(Request $request, string $token): RedirectResponse
    {
        $record = VerificationToken::where('token', $token)->first();

        if (! $record || $record->isExpired()) {
            log_activity('email_verification_failed', null, [
                'token'   => $token,
                'reason'  => 'invalid_or_expired',
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Invalid or expired verification link.',
            ]);
        }

        $user = $record->user;
        $email = $user->email ? normalize_email($user->email) : null;

        $user->markEmailAsVerified();
        $record->delete();

        log_activity('email_verified', $user, [
            'email'       => $email,
            'method'      => 'token_link',
            'token_used'  => $token,
        ]);

        return redirect('/logbook')->with('verified', true);
    }
}