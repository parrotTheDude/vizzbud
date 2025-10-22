<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Country extends Model
{
    protected $fillable = ['name', 'slug', 'code'];

    public function states() {
        return $this->hasMany(State::class);
    }

    protected static function booted() {
        static::saving(fn ($country) => $country->slug = Str::slug($country->name));
    }
}