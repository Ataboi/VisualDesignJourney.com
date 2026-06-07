<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function fontPairings()
    {
        return $this->morphedByMany(FontPairing::class, 'taggable');
    }

    public function colorPalettes()
    {
        return $this->morphedByMany(ColorPalette::class, 'taggable');
    }

    public function designPrompts()
    {
        return $this->morphedByMany(DesignPrompt::class, 'taggable');
    }

    public function websiteSections()
    {
        return $this->morphedByMany(WebsiteSection::class, 'taggable');
    }
}
