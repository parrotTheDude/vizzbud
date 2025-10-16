<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationToken;
use App\Services\PostmarkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = null;

        DB::transaction(function () use (&$user, $validated) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => 'user',
            ]);

            // 🧾 Log creation immediately
            log_activity('user_registered', $user, [
                'email' => $user->email,
                'name'  => $user->name,
            ]);

            // Check for existing token for this user
            $existing = VerificationToken::where('user_id', $user->id)->latest()->first();

            // Cooldown: prevent new token if one was just sent
            if ($existing && $existing->created_at->gt(now()->subSeconds(60))) {
                throw new \Exception('Please wait before requesting another verification email.');
            }

            // Reuse existing token if still valid
            if ($existing && $existing->expires_at->isFuture()) {
                $token = $existing->token;
            } else {
                $token = Str::random(64);

                VerificationToken::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'token'      => $token,
                        'expires_at' => now()->addHours(24),
                        'created_at' => now(),
                    ]
                );
            }

            // Build verify URL
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

                // ✅ Log success
                log_activity('verification_email_sent', $user, [
                    'email' => $user->email,
                    'token' => $token,
                    'method' => 'Postmark',
                ]);
            } catch (Throwable $e) {
                // ⚠️ Log failure
                log_activity('verification_email_failed', $user, [
                    'error' => $e->getMessage(),
                ]);

                logger()->error('Postmark verify email failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        });

        // Auto-login user
        Auth::login($user);

        // 🧾 Log login after registration
        log_activity('user_logged_in_after_registration', $user, [
            'ip' => $request->ip(),
            'agent' => substr($request->userAgent(), 0, 255),
        ]);

        return redirect()->route('verification.notice');
    }
}