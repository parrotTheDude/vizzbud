<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use League\CommonMark\MarkdownConverter;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'published_at',
        'published',
        'featured_image',
        'featured_image_alt',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function getHtmlContentAttribute(): string
    {
        return $this->content ?? '';
    }
}
