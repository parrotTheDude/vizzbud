<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // ✅ Validate input
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // 🧹 Normalize the email for consistency
        $email = normalize_email($validated['email']);
        $timestamp = now('UTC')->toDateTimeString();

        // 🚀 Attempt to send password reset link
        $status = Password::sendResetLink(['email' => $email]);

        // 🧾 Log every attempt
        log_activity('password_reset_link_requested', null, [
            'email'   => $email,
            'status'  => $status,
            'success' => $status === Password::RESET_LINK_SENT,
        ]);

        // 🔁 Return response
        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput(['email' => $email])
                    ->withErrors(['email' => __($status)]);
    }
}