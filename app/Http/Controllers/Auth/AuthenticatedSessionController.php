<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $timestamp = now('UTC')->toDateTimeString();

        $captchaResponse = Http::asForm()->post('https://api.friendlycaptcha.com/api/v1/siteverify', [
            'solution' => $request->input('frc-captcha-solution'),
            'secret'   => config('services.friendlycaptcha.secret'),
            'sitekey'  => config('services.friendlycaptcha.sitekey'),
        ]);

        if (! $captchaResponse->json('success')) {
            log_activity('login_captcha_failed', null, [
                'email' => $request->input('email'),
                'reason' => 'captcha_verification_failed',
            ]);

            return back()
                ->withErrors(['captcha' => 'Please complete the human verification.'])
                ->withInput();
        }

        try {
            // 🔐 Attempt authentication via the validated FormRequest
            $request->authenticate();

            $user = Auth::user();

            // 🔄 Auto-upgrade password hashing if outdated
            if (Hash::needsRehash($user->password)) {
                $user->password = Hash::make($request->password);
                $user->save();

                log_activity('user_password_rehashed', $user, [
                    'algorithm' => config('hashing.driver'),
                ]);
            }

            // 🧾 Log successful login
            log_activity('user_login_success', $user, [
                'email'  => $request->input('email'),
                'method' => 'form_login',
            ]);

        } catch (ValidationException $e) {
            // ❌ Log failed login attempt
            log_activity('user_login_failed', null, [
                'email'  => $request->input('email'),
                'reason' => 'invalid_credentials',
            ]);

            throw $e;
        }

        // ✅ Session regeneration for protection against fixation attacks
        $request->session()->regenerate();

        return redirect()->intended(route('logbook.index'));
    }

    /**
     * Destroy an authenticated session (logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $timestamp = now('UTC')->toDateTimeString();
        $user = Auth::user();

        if ($user) {
            // 🧾 Log logout before invalidating session
            log_activity('user_logout', $user);
        }

        // 🚪 Terminate authentication session
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}