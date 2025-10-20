<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Models\ExternalConditionDaypart;
use App\Models\DiveSitePhoto;

class DiveSite extends Model
{
    protected $fillable = [
        'name',
        'description',
        'lat',
        'lng',
        'max_depth',
        'avg_depth',
        'dive_type',
        'suitability',
        'region',    
        'country', 
        'is_active',
        'needs_review',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'max_depth' => 'float',
        'avg_depth' => 'float',
        'is_active' => 'boolean',
        'needs_review' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Normalize inputs
        static::saving(function (self $site) {
            if (isset($site->name)) {
                $site->name = trim($site->name);
            }
        });

        // Create slug if empty
        static::creating(function (self $site) {
            if (blank($site->slug)) {
                $site->slug = static::uniqueSlugFrom($site->name);
            }
        });

        // Refresh slug IF AND ONLY IF name changed and slug was derived before
        static::updating(function (self $site) {
            if ($site->isDirty('name')) {
                // If slug was never manually customized, regenerate
                $originalSlug = $site->getOriginal('slug');
                $expectedFromOldName = Str::slug($site->getOriginal('name'));
                $wasDerived = Str::startsWith($originalSlug, $expectedFromOldName);

                if ($wasDerived) {
                    $site->slug = static::uniqueSlugFrom($site->name, $site->id);
                }
            }
        });
    }

    /** Create a unique slug from a name (optionally ignore a given ID when updating). */
    protected static function uniqueSlugFrom(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'site';
        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /** Relationships */

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

    /** Route binding */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Scopes */

    /**
     * Constrain by bounding box (WGS84).
     * @param  Builder  $q
     * @param  float    $minLng
     * @param  float    $minLat
     * @param  float    $maxLng
     * @param  float    $maxLat
     */
    public function scopeWithinBBox(Builder $q, float $minLng, float $minLat, float $maxLng, float $maxLat): Builder
    {
        return $q->whereBetween('lng', [$minLng, $maxLng])
                 ->whereBetween('lat', [$minLat, $maxLat]);
    }

    /** Day-part rows (one row per {local_date, part}) */
    public function dayparts()
    {
        return $this->hasMany(ExternalConditionDaypart::class)
            ->orderBy('local_date')
            ->orderBy('part');
    }

    /** Upcoming dayparts grouped by local date for N days */
    public function upcomingDayparts(int $days = 3)
    {
        $tz   = $this->timezone ?? 'UTC';
        $from = now($tz)->toDateString();
        $to   = now($tz)->addDays($days - 1)->toDateString();

        return $this->dayparts()
            ->whereBetween('local_date', [$from, $to]);
    }

    /** Convenience: today's 3-part summary (null if none) */
    public function getTodaySummaryAttribute()
    {
        $tz   = $this->timezone ?? 'UTC';
        $today = now($tz)->toDateString();

        // If relationship is loaded, use that; otherwise query cheaply
        $rows = $this->relationLoaded('dayparts')
            ? $this->dayparts->where('local_date', $today)
            : $this->dayparts()->where('local_date', $today)->get(['part','status']);

        if ($rows->isEmpty()) return null;

        $byPart = $rows->pluck('status', 'part');

        return [
            'morning'   => $byPart['morning']   ?? null,
            'afternoon' => $byPart['afternoon'] ?? null,
            'night'     => $byPart['night']     ?? null,
        ];
    }

    public function photos()
    {
        return $this->hasMany(DiveSitePhoto::class)->orderBy('order');
    }

    public function featuredPhoto()
    {
        return $this->hasOne(DiveSitePhoto::class)->where('is_featured', true);
    }

    public function nearbySites($limit = 3)
    {
        return static::query()
            ->with(['photos' => function ($q) {
                $q->where('is_featured', true)->limit(1);
            }])
            ->where('id', '!=', $this->id)
            ->select('id', 'name', 'slug', 'lat', 'lng', 'region', 'country')
            ->selectRaw('
                (6371 * acos(
                    cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) +
                    sin(radians(?)) * sin(radians(lat))
                )) AS distance', [$this->lat, $this->lng, $this->lat])
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }
}