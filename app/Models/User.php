<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\PostmarkService;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_url',
        'bio',
        'certification',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationship: all dive logs created by this user.
     */
    public function diveLogs()
    {
        return $this->hasMany(UserDiveLog::class);
    }

    /**
     * Derived: get initials (useful for avatars without images)
     */
    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', $this->name))
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->join('');
    }

    /**
     * Derived: quick profile completeness percentage
     */
    public function getProfileCompletionAttribute(): int
    {
        $fields = ['avatar_url', 'bio', 'certification'];
        $filled = collect($fields)->filter(fn($f) => !empty($this->$f))->count();
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

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }
}