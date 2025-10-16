<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Show the "Forgot Password" form.
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle sending the password reset link.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // 🧹 Normalize email for consistent lookup
        $email = normalize_email($validated['email']);

        // 🕓 Timestamp for logging
        $timestamp = now('UTC')->toDateTimeString();

        // 📝 Always log the attempt
        log_activity('password_reset_link_requested', null, [
            'email' => $email,
        ]);

        // Attempt to send password reset link
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            log_activity('password_reset_link_sent', null, [
                'email' => $email,
                'ip'    => $request->ip(),
                'agent' => substr($request->userAgent(), 0, 255),
                'time'  => $timestamp,
            ]);

            return back()->with(['status' => __($status)]);
        }

        // ❌ Failed — usually "user not found"
        log_activity('password_reset_link_failed', null, [
            'email'  => $email,
            'status' => $status,
        ]);

        return back()->withErrors(['email' => __($status)]);
    }
}