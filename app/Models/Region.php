<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Region extends Model
{
    protected $fillable = ['state_id', 'name', 'slug'];

    public function state() {
        return $this->belongsTo(State::class);
    }

    public function diveSites() {
        return $this->hasMany(DiveSite::class);
    }

    protected static function booted() {
        static::saving(fn ($region) => $region->slug = Str::slug($region->name));
    }
}