<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'color', 'description', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function fontPairings()
    {
        return $this->hasMany(FontPairing::class);
    }

    public function colorPalettes()
    {
        return $this->hasMany(ColorPalette::class);
    }

    public function designPrompts()
    {
        return $this->hasMany(DesignPrompt::class);
    }

    public function websiteSections()
    {
        return $this->hasMany(WebsiteSection::class);
    }

    public function briefTemplates()
    {
        return $this->hasMany(BriefTemplate::class);
    }
}
