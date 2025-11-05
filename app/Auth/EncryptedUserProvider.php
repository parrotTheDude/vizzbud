<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class EncryptedUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        $email = strtolower(trim($credentials['email'] ?? ''));
        $pepper = config('app.email_pepper');

        if (empty($email) || empty($pepper)) {
            return null;
        }

        // ✅ Compute deterministic lookup hash (must match User::setEmailAttribute)
        $emailHash = hash_hmac('sha256', $email, $pepper);

        // 🔎 Query using hash only — never by email directly
        return $this->createModel()
            ->newQuery()
            ->where('email_hash', $emailHash)
            ->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'] ?? '';

        // Let Laravel handle bcrypt or argon2 as normal
        return Hash::check($plain, $user->getAuthPassword());
    }
}