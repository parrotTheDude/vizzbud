<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiveSitePhoto extends Model
{
    protected $fillable = [
        'dive_site_id',
        'image_path',
        'caption',
        'artist_name',
        'artist_instagram',
        'artist_website',
        'is_featured',
        'order',
    ];

    public function diveSite()
    {
        return $this->belongsTo(DiveSite::class);
    }

    // Simple accessor for a clickable credit link
    public function getCreditHtmlAttribute(): string
    {
        if ($this->artist_instagram) {
            return '<a href="https://www.instagram.com/' . ltrim($this->artist_instagram, '@') . '" 
                        target="_blank" rel="noopener noreferrer">@' . e($this->artist_name ?? $this->artist_instagram) . '</a>';
        }
        if ($this->artist_website) {
            return '<a href="' . e($this->artist_website) . '" target="_blank" rel="noopener noreferrer">' . e($this->artist_name ?? $this->artist_website) . '</a>';
        }
        return e($this->artist_name ?? 'Unknown');
    }
}