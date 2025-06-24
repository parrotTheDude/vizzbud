<?php

// app/Models/UserDiveLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDiveLog extends Model
{
    protected $fillable = [
        'user_id', 'dive_site_id', 'dive_date', 'depth', 'duration',
        'buddy', 'notes', 'air_start', 'air_end', 'temperature',
        'suit_type', 'tank_type', 'weight_used', 'visibility', 'rating',
        'title', // ✅ Add this line
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(DiveSite::class, 'dive_site_id');
    }
}