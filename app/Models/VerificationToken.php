<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationToken extends Model
{
    public $timestamps = true;

    protected $fillable = ['user_id', 'token', 'expires_at'];

    // 🧭 Make sure date fields become Carbon instances
    protected $casts = [
        'expires_at' => 'immutable_datetime:UTC',
        'created_at' => 'immutable_datetime:UTC',
        'updated_at' => 'immutable_datetime:UTC',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }
    
    public function withinCooldown(int $seconds = 90): bool
    {
        if (! $this->created_at) return false;

        $createdUtc = $this->created_at->copy()->setTimezone('UTC');
        $nowUtc     = now('UTC');

        return $createdUtc->diffInSeconds($nowUtc) < $seconds;
    }
}