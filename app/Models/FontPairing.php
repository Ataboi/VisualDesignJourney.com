<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FontPairing extends Model
{
    protected $fillable = [
        'name', 'slug', 'heading_font', 'body_font', 'heading_weight', 'body_weight',
        'heading_size', 'body_size', 'style', 'description', 'preview_text',
        'google_fonts', 'category_id', 'is_featured', 'is_active', 'view_count',
    ];

    protected $casts = [
        'google_fonts' => 'array',
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

    public function getGoogleFontsUrlAttribute(): string
    {
        $fonts = collect([$this->heading_font, $this->body_font])->unique()->map(fn($f) => urlencode($f));
        return 'https://fonts.googleapis.com/css2?family=' . $fonts->implode('&family=') . '&display=swap';
    }
}
