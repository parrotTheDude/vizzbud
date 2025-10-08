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

        // Limit to 3 attempts per 60s
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }
        RateLimiter::hit($key, 60);

        if ($user->hasVerifiedEmail()) {
            // Already verified → send them on their way
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Create/refresh token (24h)
        $token = Str::random(64);
        VerificationToken::updateOrCreate(
            ['user_id' => $user->id],
            ['token' => $token, 'expires_at' => now()->addHours(24)]
        );

        $verifyUrl = url(route('verify.email', ['token' => $token], false));

        // Send via Postmark
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

            return back()->with('status', 'Verification link sent!');
        } catch (Throwable $e) {
            // Log but don't expose internals to the user
            logger()->error('Postmark verification resend failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('status', 'We couldn’t send the email just now. Please try again shortly.');
        }
    }
}