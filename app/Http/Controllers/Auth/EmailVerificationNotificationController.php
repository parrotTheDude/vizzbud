<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\VerificationToken;
use App\Services\PostmarkService;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification using custom Postmark service.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Create or update the verification token
        $token = Str::random(64);

        VerificationToken::updateOrCreate(
            ['user_id' => $user->id],
            ['token' => $token, 'expires_at' => now()->addHours(24)]
        );

        // Send verification email using your custom PostmarkService
        $verifyUrl = url(route('verify.email', ['token' => $token], false));

        app(PostmarkService::class)->sendEmail(
            39981023, // Your Postmark template ID
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