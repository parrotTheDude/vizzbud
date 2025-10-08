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
        $token = Str::random(64);

        DB::transaction(function () use (&$user, $validated, $token) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => 'user',
            ]);

            // Clean any old tokens for this user (defensive)
            VerificationToken::where('user_id', $user->id)->delete();

            VerificationToken::create([
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => now()->addHours(24),
            ]);
        });

        // Build verify URL for your custom route
        $verifyUrl = url(route('verify.email', ['token' => $token], false));

        // Send with Postmark (uses your service wrapper)
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
        } catch (Throwable $e) {
            // Log but don't block sign-up; user can request a re-send
            logger()->error('Postmark verify email failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}