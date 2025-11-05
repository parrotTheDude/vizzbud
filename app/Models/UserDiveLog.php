<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;

class UserDiveLog extends Model
{
    protected $fillable = [
        'user_id', 'dive_site_id', 'dive_date', 'depth', 'duration',
        'buddy', 'notes', 'air_start', 'air_end', 'temperature',
        'suit_type', 'tank_type', 'weight_used', 'visibility', 'rating',
        'title',
    ];

    protected $casts = [
        'dive_date'   => 'datetime',
        'depth'       => 'float',
        'duration'    => 'integer',
        'rating'      => 'integer',

        // Encrypted fields
        'title' => 'encrypted',
        'notes' => 'encrypted',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(DiveSite::class, 'dive_site_id');
    }

    /**
     * Quick accessor: full site name or "Unknown"
     */
    public function getSiteNameAttribute(): string
    {
        return $this->site?->name ?? 'Unknown Site';
    }

    /**
     * Graceful decryption fallback — prevents fatal errors
     */
    public function getAttribute($key)
    {
        try {
            return parent::getAttribute($key);
        } catch (DecryptException $e) {
            logger()->warning("Decrypt failed for user_dive_logs.$key", [
                'id' => $this->id,
                'user_id' => $this->user_id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}