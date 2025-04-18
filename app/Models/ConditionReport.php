<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConditionReport extends Model
{
    protected $fillable = [
        'dive_site_id',
        'viz_rating',
        'comment',
        'reported_at',
    ];

    public function site()
    {
        return $this->belongsTo(DiveSite::class, 'dive_site_id');
    }
}
