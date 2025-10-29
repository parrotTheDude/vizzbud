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
    ];

    /**
     * Relationship: profile belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: profile has one dive level.
     */
    public function diveLevel()
    {
        return $this->belongsTo(DiveLevel::class);
    }
}