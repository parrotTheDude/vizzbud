<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'suitability'
    ];
    
    public function latestCondition()
    {
        return $this->hasOne(ExternalCondition::class)
                    ->latestOfMany('retrieved_at');
    }
}
