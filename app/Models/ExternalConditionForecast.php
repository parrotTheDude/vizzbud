<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExternalConditionForecast extends Model
{
    use HasFactory;

    protected $table = 'external_condition_forecasts';

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

        'score',
        'status',
    ];

    protected $casts = [
        'forecast_time'     => 'datetime',

        'wave_height'       => 'float',
        'wave_period'       => 'float',
        'wave_direction'    => 'float',
        'water_temperature' => 'float',

        'wind_speed'        => 'float',
        'wind_direction'    => 'float',
        'air_temperature'   => 'float',

        'score'             => 'float',
        'status'            => 'string',
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