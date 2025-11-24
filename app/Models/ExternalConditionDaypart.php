<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalConditionDaypart extends Model
{
    protected $table = 'external_condition_dayparts';

    protected $fillable = [
        'dive_site_id',
        'local_date',
        'part',
        'status',
        'wave_height_max',
        'wind_speed_max',
        'wave_period_max',
        'swell_dir_avg',
        'wind_dir_avg',
        'score',

        'computed_at',
    ];

    protected $casts = [
        'local_date'       => 'date',

        'wave_height_max'  => 'float',
        'wind_speed_max'   => 'float',
        'wave_period_max'  => 'float',
        'swell_dir_avg'    => 'float',
        'wind_dir_avg'     => 'float',
        'score'        => 'float',

        'computed_at'      => 'datetime',
    ];

    public function diveSite()
    {
        return $this->belongsTo(DiveSite::class);
    }
}