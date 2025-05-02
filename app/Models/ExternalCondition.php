<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalCondition extends Model
{
    protected $fillable = [
        'dive_site_id', 'retrieved_at', 'status',
        'wave_height', 'wave_period', 'wave_direction',
        'water_temperature', 'wind_speed', 'wind_direction',
        'air_temperature'
    ];

    protected $casts = [
        'data' => 'array',
        'retrieved_at' => 'datetime',
    ];
}
