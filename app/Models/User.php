<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\PostmarkService;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'name'              => 'encrypted',
            'email'             => 'encrypted',
        ];
    }

    /**
     * Handle encrypted email + deterministic lookup hash.
     * Prevents re-encrypting already-encrypted values.
     */
    public function setEmailAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['email'] = null;
            $this->attributes['email_hash'] = null;
            return;
        }

        $pepper = config('app.email_pepper');
        $normalized = strtolower(trim($value));

        // Detect if value looks already encrypted (Laravel encrypted strings start with eyJpdi)
        if (str_starts_with($value, 'eyJpdi')) {
            // already encrypted, just assign it
            $this->attributes['email'] = $value;
        } else {
            // encrypt plaintext
            $this->attributes['email'] = encrypt($value);
        }

        // always compute deterministic hash from plaintext
        $this->attributes['email_hash'] = hash_hmac('sha256', $normalized, $pepper);
    }

    /**
     * Relationship: all dive logs created by this user.
     */
    public function diveLogs()
    {
        return $this->hasMany(UserDiveLog::class);
    }

    /**
     * Derived: initials for avatar placeholders.
     */
    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', $this->name))
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->join('');
    }

    /**
     * Derived: profile completion percentage.
     */
    public function getProfileCompletionAttribute(): int
    {
        if (!$this->relationLoaded('profile')) {
            $this->load('profile');
        }

        $profile = $this->profile;
        if (!$profile) return 0;

        $fields = ['avatar_url', 'bio', 'dive_level_id'];
        $filled = collect($fields)->filter(fn($f) => !empty($profile->$f))->count();
        return round(($filled / count($fields)) * 100);
    }

    /**
     * Send password reset email using Postmark.
     */
    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ], false));

        $postmark = app(PostmarkService::class);

        $postmark->sendEmail(
            templateId: (int) config('services.postmark.reset_template_id'),
            to: $this->email,
            variables: [
                'name'          => $this->name,
                'action_url'    => $resetUrl,
                'support_email' => config('mail.from.address'),
                'year'          => now()->year,
            ],
            tag: 'password-reset',
            options: [
                'replyTo'  => config('mail.from.address'),
                'metadata' => ['user_id' => (string) $this->id],
            ]
        );
    }

    /**
     * Determine if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Linked profile.
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }
}