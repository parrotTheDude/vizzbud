<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Attempt password validation
        if (! Auth::guard('web')->validate([
            'email' => $user->email,
            'password' => $request->password,
        ])) {
            // ❌ Log failed confirmation attempt
            log_activity('password_confirmation_failed', $user, [
                'ip' => $request->ip(),
                'agent' => substr($request->userAgent(), 0, 255),
            ]);

            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        // ✅ Password confirmed successfully
        $request->session()->put('auth.password_confirmed_at', time());

        log_activity('password_confirmed', $user, [
            'ip' => $request->ip(),
            'agent' => substr($request->userAgent(), 0, 255),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}