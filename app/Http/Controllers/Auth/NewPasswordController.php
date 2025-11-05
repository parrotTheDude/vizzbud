<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[a-z]/',      // At least one lowercase
                'regex:/[A-Z]/',      // At least one uppercase
                'regex:/[0-9]/',      // At least one digit
                'regex:/[@$!%*#?&]/', // At least one symbol
            ],
        ]);

        // 🧹 Normalize email
        $email = normalize_email($validated['email']);
        $timestamp = now('UTC')->toDateTimeString();

        // 🧾 Log initial attempt
        log_activity('password_reset_attempted', null, [
            'email' => $email,
        ]);

        $status = Password::reset(
            [
                'email' => $email,
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user) use ($validated) {
                $user->forceFill([
                    'password'       => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                // ✅ Log success
                log_activity('password_reset_successful', $user, [
                    'email' => $user->email,
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        // ❌ Log failure
        log_activity('password_reset_failed', null, [
            'email'  => $email,
            'status' => $status,
        ]);

        return back()
            ->withInput(['email' => $email])
            ->withErrors(['email' => __($status)]);
    }
}