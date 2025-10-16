<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'dive_site_id',
        'dive_site_name',
        'name',
        'email',
        'category',
        'message',
        'reviewed',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function diveSite()
    {
        return $this->belongsTo(DiveSite::class);
    }
}