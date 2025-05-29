<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DiveSite extends Model
{    
    protected static function booted()
    {
        static::creating(function ($site) {
            $site->slug = Str::slug($site->name);
        });

        static::updating(function ($site) {
            // Optional: Only update if the name changed
            if ($site->isDirty('name')) {
                $site->slug = Str::slug($site->name);
            }
        });
    }

    protected $fillable = [
        'name',
        'description',
        'lat',
        'lng',
        'max_depth',
        'avg_depth',
        'dive_type',
        'suitability'
    ];
    
    public function latestCondition()
    {
        return $this->hasOne(ExternalCondition::class)
                    ->latestOfMany('retrieved_at');
    }

    public function forecasts()
    {
        return $this->hasMany(ExternalConditionForecast::class);
    }

    public function latestForecast()
    {
        return $this->hasOne(ExternalConditionForecast::class)
                    ->latestOfMany('forecast_time');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function diveLogs()
    {
        return $this->hasMany(\App\Models\DiveLog::class, 'dive_site_id');
    }
}
