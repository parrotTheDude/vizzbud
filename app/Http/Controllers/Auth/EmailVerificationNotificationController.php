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
        $timestamp = now('UTC')->toDateTimeString();
        $key = 'resend-verification:' . $user->id;

        // 🔒 Global abuse limiter: max 3 requests per 60s per user
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            log_activity('verification_email_rate_limited', $user, [
                'remaining_cooldown' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 60);

        // ✅ Already verified? Redirect
        if ($user->hasVerifiedEmail()) {
            log_activity('verification_email_skipped_already_verified', $user, [
            ]);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // 🔍 Fetch most recent token (if any)
        $existing = VerificationToken::where('user_id', $user->id)->latest()->first();

        // 🕒 Enforce 90-second cooldown (compare in UTC)
        if ($existing && $existing->updated_at?->gt(now('UTC')->subSeconds(90))) {
            $remaining = max(1, $existing->updated_at->addSeconds(90)->diffInSeconds(now('UTC'), false) * -1);

            log_activity('verification_email_cooldown_active', $user, [
                'remaining_seconds' => $remaining,
            ]);

            return back()->withErrors([
                'cooldown' => "Please wait {$remaining} seconds before requesting another verification email.",
            ]);
        }

        // ♻️ Reuse token if still valid, else create new
        if ($existing && $existing->expires_at?->isFuture()) {
            $token = $existing->token;
            $existing->touch(); // refresh updated_at for cooldown tracking
            $status = 'token_reused';
        } else {
            $token = Str::random(64);

            VerificationToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'token'      => $token,
                    'expires_at' => now('UTC')->addHours(24),
                    'updated_at' => now('UTC'),
                ]
            );
            $status = 'token_created';
        }

        $verifyUrl = url(route('verify.email', ['token' => $token], false));

        // ✉️ Send via Postmark
        try {
            app(PostmarkService::class)->sendEmail(
                templateId: (int) config('services.postmark.verify_template_id'),
                to: $user->email,
                variables: [
                    'name'          => $user->name ?? 'there',
                    'action_url'    => $verifyUrl,
                    'support_email' => config('mail.from.address'),
                    'year'          => now('UTC')->year,
                ],
                tag: 'email-verification',
                options: [
                    'replyTo'  => config('mail.from.address'),
                    'metadata' => ['user_id' => (string) $user->id],
                ]
            );

            log_activity('verification_email_sent', $user, [
                'email'  => $user->email,
                'token'  => $token,
                'status' => $status,
            ]);

            return back()->with('status', 'verification-link-sent');
        } catch (Throwable $e) {
            log_activity('verification_email_failed', $user, [
                'error'  => $e->getMessage(),
            ]);

            logger()->error('Postmark verification resend failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'time_utc' => $timestamp,
            ]);

            return back()->with('status', 'We couldn’t send the email just now. Please try again shortly.');
        }
    }
}