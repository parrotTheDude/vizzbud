<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

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
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $captchaResponse = Http::asForm()->post('https://api.friendlycaptcha.com/api/v1/siteverify', [
            'solution' => $request->input('frc-captcha-solution'),
            'secret'   => config('services.friendlycaptcha.secret'),
            'sitekey'  => config('services.friendlycaptcha.sitekey'),
        ]);

        $captchaData = $captchaResponse->json();

        if (!($captchaData['success'] ?? false)) {
            throw ValidationException::withMessages([
                'captcha' => 'Captcha verification failed. Please try again.',
            ]);
        }

        $email = normalize_email($validated['email']);
        $pepper = config('app.email_pepper');
        $emailHash = hash_hmac('sha256', strtolower(trim($email)), $pepper);

        // 🔍 Lookup user using deterministic hash
        $user = \App\Models\User::where('email_hash', $emailHash)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'We have emailed you a password reset link.']);
        }

        // 🧠 Create token
        $token = app('auth.password.broker')->createToken($user);

        // 📨 Send reset email via Postmark
        app(\App\Services\PostmarkService::class)->sendEmail(
            templateId: (int) config('services.postmark.reset_template_id'),
            to: $user->email,
            variables: [
                'name'          => $user->name,
                'action_url'    => url(route('password.reset', ['token' => $token, 'email' => $email], false)),
                'support_email' => config('mail.from.address'),
                'year'          => now('UTC')->year,
            ],
            tag: 'password-reset'
        );

        log_activity('password_reset_link_sent', $user, ['email' => $email]);

        return back()->with('status', __('passwords.sent'));
    }
}