<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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

        try {
            // 🔐 Attempt authentication via the validated FormRequest
            $request->authenticate();

            // 🧾 Log successful login
            log_activity('user_login_success', Auth::user(), [
                'email'  => $request->input('email'),
                'method' => 'form_login',
            ]);

        } catch (ValidationException $e) {
            // ❌ Log failed login attempt
            log_activity('user_login_failed', null, [
                'email'  => $request->input('email'),
                'reason' => 'invalid_credentials',
            ]);

            throw $e; // rethrow for Laravel's normal validation error response
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
            log_activity('user_logout', $user, [
            ]);
        }

        // 🚪 Terminate authentication session
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}