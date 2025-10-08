<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ExternalCondition extends Model
{
    protected $fillable = [
        'dive_site_id', 'retrieved_at', 'status',
        'wave_height', 'wave_period', 'wave_direction',
        'water_temperature', 'wind_speed', 'wind_direction',
        'air_temperature',
    ];

    protected $casts = [
        'retrieved_at'      => 'datetime',
        'wave_height'       => 'float',
        'wave_period'       => 'float',
        'wave_direction'    => 'float',
        'water_temperature' => 'float',
        'wind_speed'        => 'float',
        'wind_direction'    => 'float',
        'air_temperature'   => 'float',
    ];

    /* Relationships */
    public function diveSite()
    {
        return $this->belongsTo(DiveSite::class);
    }

    /* Scopes */

    /**
     * Latest row per dive_site_id for a given list of site IDs.
     * Usage: ExternalCondition::latestForSites([1,2,3])->get();
     */
    public function scopeLatestForSites(Builder $q, array $siteIds): Builder
    {
        if (empty($siteIds)) return $q->whereRaw('0=1');

        // subquery: max(retrieved_at) per site
        $sub = static::query()
            ->selectRaw('dive_site_id, MAX(retrieved_at) as max_rt')
            ->whereIn('dive_site_id', $siteIds)
            ->groupBy('dive_site_id');

        return $q->joinSub($sub, 'lc', function ($join) {
                $join->on('external_conditions.dive_site_id', '=', 'lc.dive_site_id')
                     ->on('external_conditions.retrieved_at', '=', 'lc.max_rt');
            })
            ->whereIn('external_conditions.dive_site_id', $siteIds)
            ->select('external_conditions.*');
    }

    /**
     * Conditions whose retrieved_at is within the past N hours.
     * Default 6h.
     */
    public function scopeRecent(Builder $q, int $hours = 6): Builder
    {
        return $q->where('retrieved_at', '>=', now()->subHours($hours)->startOfHour());
    }

    /**
     * Conditions for a specific hour (UTC-aligned).
     */
    public function scopeForHour(Builder $q, Carbon|string $hour): Builder
    {
        $h = $hour instanceof Carbon ? $hour->copy()->minute(0)->second(0) : Carbon::parse($hour)->minute(0)->second(0);
        return $q->where('retrieved_at', $h);
    }

    /* Helpers (optional, keep UI logic out of controllers) */

    public function getIsStaleAttribute(): bool
    {
        return optional($this->retrieved_at)?->lt(now()->subHours(3)->startOfHour()) ?? true;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'green'  => '#22c55e',
            'yellow' => '#eab308',
            'red'    => '#ef4444',
            default  => '#94a3b8', // unknown/grey
        };
    }
}