<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiveLevel extends Model
{
    use HasFactory;

    protected $table = 'dive_levels';

    protected $fillable = [
        'name',
        'rank',
    ];

    /**
     * Order dive levels by rank by default.
     */
    protected static function booted()
    {
        static::addGlobalScope('orderByRank', function ($query) {
            $query->orderBy('rank');
        });
    }

    /**
     * A dive level can belong to many users.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'dive_level_id');
    }
}