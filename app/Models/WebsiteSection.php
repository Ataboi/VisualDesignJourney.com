<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSection extends Model
{
    protected $fillable = [
        'title', 'slug', 'section_type', 'style', 'description', 'preview_image',
        'source_url', 'category_id', 'is_featured', 'is_active', 'view_count',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favorable');
    }
}
