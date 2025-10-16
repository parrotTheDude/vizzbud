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
     * Update the authenticated user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $timestamp = now('UTC')->toDateTimeString();

        try {
            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            // ❌ Validation failed (wrong current password, etc.)
            log_activity('password_update_failed', $request->user(), [
                'reason' => 'validation_error',
            ]);

            throw $e;
        }

        // 🧾 Log attempt
        log_activity('password_update_attempted', $request->user(), [
        ]);

        // ✅ Perform update
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // ✅ Log success
        log_activity('password_update_successful', $request->user(), [
        ]);

        return back()->with('status', 'password-updated');
    }
}