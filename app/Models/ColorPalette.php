<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColorPalette extends Model
{
    protected $fillable = [
        'name', 'slug', 'colors', 'mood', 'description', 'category_id',
        'is_featured', 'is_active', 'view_count',
    ];

    protected $casts = [
        'colors' => 'array',
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
