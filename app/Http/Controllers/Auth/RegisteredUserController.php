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
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 🧹 Normalize email
        $email = strtolower(trim($validated['email']));
        $pepper = config('app.email_pepper');
        $emailHash = hash_hmac('sha256', $email, $pepper);

        // 🚫 Check if already registered
        if (User::where('email_hash', $emailHash)->exists()) {
            return back()
                ->withErrors(['email' => 'This email is already taken.'])
                ->withInput();
        }

        $user = null;

        DB::transaction(function () use (&$user, $validated, $email) {
            // 🧾 Create user (email gets encrypted automatically via cast)
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $email,
                'password' => Hash::make($validated['password']),
                'role'     => 'user',
            ]);

            log_activity('user_registered', $user, [
                'email' => $user->email,
                'name'  => $user->name,
            ]);

            // 🔁 Handle verification token (reuse or create)
            $existing = VerificationToken::where('user_id', $user->id)->latest()->first();

            if ($existing && $existing->created_at->gt(now('UTC')->subSeconds(60))) {
                throw new \Exception('Please wait before requesting another verification email.');
            }

            $token = $existing && $existing->expires_at->isFuture()
                ? $existing->token
                : Str::random(64);

            VerificationToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'token'      => $token,
                    'expires_at' => now('UTC')->addHours(24),
                    'created_at' => now('UTC'),
                ]
            );

            $verifyUrl = url(route('verify.email', ['token' => $token], false));

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
                    'method' => 'Postmark',
                ]);
            } catch (Throwable $e) {
                log_activity('verification_email_failed', $user, [
                    'error' => $e->getMessage(),
                ]);
                logger()->error('Postmark verify email failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        });

        // 🚪 Auto-login
        Auth::login($user);

        log_activity('user_logged_in_after_registration', $user, []);

        return redirect()->route('verification.notice');
    }
}