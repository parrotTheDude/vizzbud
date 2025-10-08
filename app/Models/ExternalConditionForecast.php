<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExternalConditionForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'dive_site_id',
        'forecast_time',
        'wave_height',
        'wave_period',
        'wave_direction',
        'water_temperature',
        'wind_speed',
        'wind_direction',
        'air_temperature',
    ];

    protected $casts = [
        'forecast_time' => 'datetime',
    ];

    public $timestamps = true;

    public function diveSite()
    {
        return $this->belongsTo(DiveSite::class);
    }

    /** Grab upcoming rows from now (aligned to the hour), limit N */
    public function scopeUpcoming($q, int $limit = 48)
    {
        return $q->where('forecast_time', '>=', now()->startOfHour())
                 ->orderBy('forecast_time')
                 ->limit($limit);
    }
}