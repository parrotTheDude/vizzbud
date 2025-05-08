<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationToken extends Model
{
    protected $fillable = ['user_id', 'token', 'expires_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }
}