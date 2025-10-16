<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            // ❌ Validation failed (wrong current password, etc.)
            log_activity('password_update_failed', $request->user(), [
                'reason' => 'validation_error',
                'ip'     => $request->ip(),
                'agent'  => substr($request->userAgent(), 0, 255),
            ]);

            throw $e;
        }

        // 🧾 Log attempt before update
        log_activity('password_update_attempted', $request->user(), [
            'ip'    => $request->ip(),
            'agent' => substr($request->userAgent(), 0, 255),
        ]);

        // ✅ Perform update
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // ✅ Log success
        log_activity('password_update_successful', $request->user(), [
            'ip'    => $request->ip(),
            'agent' => substr($request->userAgent(), 0, 255),
        ]);

        return back()->with('status', 'password-updated');
    }
}