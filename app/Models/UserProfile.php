<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'dive_level_id',
        'avatar_url',
        'bio',
        'onboarding_complete',
    ];

    protected $casts = [
        'bio' => 'encrypted',
        'onboarding_complete' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function diveLevel()
    {
        return $this->belongsTo(DiveLevel::class);
    }
}