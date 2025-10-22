<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class State extends Model
{
    protected $fillable = ['country_id', 'name', 'slug', 'abbreviation'];

    public function country() {
        return $this->belongsTo(Country::class);
    }

    public function regions() {
        return $this->hasMany(Region::class);
    }

    protected static function booted() {
        static::saving(fn ($state) => $state->slug = Str::slug($state->name));
    }
}