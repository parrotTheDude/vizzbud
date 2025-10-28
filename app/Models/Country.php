<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Country extends Model
{
    protected $fillable = ['name', 'slug', 'code'];

    protected static function booted() {
        static::saving(fn ($country) => $country->slug = Str::slug($country->name));
    }

    public function states()
    {
        return $this->hasMany(State::class);
    }

    // Shortcut: all dive sites through states → regions
    public function diveSites()
    {
        return $this->hasManyThrough(DiveSite::class, Region::class, 'state_id', 'region_id');
    }
}