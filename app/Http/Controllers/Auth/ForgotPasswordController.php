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
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');

        // 📝 Log attempt (regardless of success)
        log_activity('password_reset_link_requested', null, [
            'email' => $email,
            'ip'    => $request->ip(),
            'agent' => substr($request->userAgent(), 0, 255),
        ]);

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            // ✅ Successfully sent
            log_activity('password_reset_link_sent', null, [
                'email' => $email,
                'ip'    => $request->ip(),
                'agent' => substr($request->userAgent(), 0, 255),
            ]);

            return back()->with(['status' => __($status)]);
        }

        // ❌ Failed (email not found or invalid)
        log_activity('password_reset_link_failed', null, [
            'email' => $email,
            'status' => $status,
            'ip'     => $request->ip(),
            'agent'  => substr($request->userAgent(), 0, 255),
        ]);

        return back()->withErrors(['email' => __($status)]);
    }
}