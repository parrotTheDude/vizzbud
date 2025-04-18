<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalCondition extends Model
{
    protected $fillable = [
        'dive_site_id',
        'data',
        'retrieved_at',
    ];

    protected $casts = [
        'data' => 'array',
        'retrieved_at' => 'datetime',
    ];
}
