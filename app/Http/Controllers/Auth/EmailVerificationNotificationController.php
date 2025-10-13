<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\VerificationToken;
use App\Services\PostmarkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $key  = 'resend-verification:' . $user->id;

        // 🔒 Global limiter: 3 requests per 60s
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 60);

        // ✅ Already verified? Redirect
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // 🔍 Check for existing token
        $existing = VerificationToken::where('user_id', $user->id)->latest()->first();

        if ($existing && $existing->updated_at->gt(now()->subSeconds(90))) {
            $remaining = (int) ceil($existing->updated_at->addSeconds(90)->diffInSeconds(now()));
            return back()
                ->withErrors([
                    'cooldown' => "Please wait {$remaining} seconds before requesting another verification email.",
                ])
                ->with('cooldown_seconds', $remaining);
        }

        // ♻️ Reuse token if still valid
        if ($existing && $existing->expires_at->isFuture()) {
            $token = $existing->token;
            $existing->touch(); // refreshes updated_at to restart cooldown
        } else {
            // 🆕 Create new token (24h expiry)
            $token = Str::random(64);

            VerificationToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'token'      => $token,
                    'expires_at' => now()->addHours(24),
                ]
            );
        }

        $verifyUrl = url(route('verify.email', ['token' => $token], false));

        logger()->info('Cooldown debug', [
            'created_at' => $existing?->created_at,
            'now' => now(),
            'diff_seconds' => $existing ? now()->diffInSeconds($existing->created_at) : null,
            'app_timezone' => config('app.timezone'),
        ]);

        // ✉️ Send via Postmark
        try {
            app(PostmarkService::class)->sendEmail(
                templateId: (int) config('services.postmark.verify_template_id'),
                to: $user->email,
                variables: [
                    'name'          => $user->name ?? 'there',
                    'action_url'    => $verifyUrl,
                    'support_email' => config('mail.from.address'),
                    'year'          => now()->year,
                ],
                tag: 'email-verification',
                options: [
                    'replyTo'  => config('mail.from.address'),
                    'metadata' => ['user_id' => (string) $user->id],
                ]
            );

            return redirect()
                ->route('verification.notice')
                ->with('status', 'verification-link-sent');
        } catch (Throwable $e) {
            logger()->error('Postmark verification resend failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return redirect()
                ->route('verification.notice')
                ->with('status', 'We couldn’t send the email just now. Please try again shortly.');
        }

    }
}