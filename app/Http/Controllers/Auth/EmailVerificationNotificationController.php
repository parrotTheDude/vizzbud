<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\VerificationToken;
use App\Services\PostmarkService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $key = 'resend-verification:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Please try again in ' . RateLimiter::availableIn($key) . ' seconds.',
            ]);
        }

        RateLimiter::hit($key, 60); // 3 attempts per 60 seconds

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Create or update token
        $token = Str::random(64);
        \App\Models\VerificationToken::updateOrCreate(
            ['user_id' => $user->id],
            ['token' => $token, 'expires_at' => now()->addHours(24)]
        );

        // Send email
        $verifyUrl = url(route('verify.email', ['token' => $token], false));

        app(\App\Services\PostmarkService::class)->sendEmail(
            39981023,
            $user->email,
            [
                'action_url' => $verifyUrl,
                'support_email' => config('mail.from.address'),
                'year' => now()->year,
                'name' => $user->name,
            ],
            alias: 'email-verification'
        );

        return back()->with('status', 'Verification link resent!');
    }
}