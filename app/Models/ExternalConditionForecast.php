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

    public $timestamps = true;

    public function diveSite()
    {
        return $this->belongsTo(DiveSite::class);
    }
}