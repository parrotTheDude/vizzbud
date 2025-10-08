<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
        // deliberately omit 'slug' from fillable
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'max_depth' => 'float',
        'avg_depth' => 'float',
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

    public function diveLogs()
    {
        return $this->hasMany(DiveLog::class, 'dive_site_id');
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

    /**
     * Eager load forecasts within a window (hours).
     * Usage: DiveSite::withForecastWindow(48)->get()
     */
    public function scopeWithForecastWindow(Builder $q, int $hours = 48): Builder
    {
        return $q->with(['forecasts' => function ($fq) use ($hours) {
            $fq->where('forecast_time', '>=', now()->startOfHour())
               ->orderBy('forecast_time')
               ->limit($hours);
        }]);
    }
}