<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'blog_posts';

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function categories()
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_post_categories', 'post_id', 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tags', 'post_id', 'tag_id');
    }

    public function localizedSlug(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $column = match ($locale) {
            'tr' => 'slug_tr',
            'de' => 'slug_de',
            default => 'slug',
        };

        return (string) ($this->{$column} ?: $this->slug);
    }

    public function localizedTitle(?string $locale = null): string
    {
        return $this->localizedValue('title', $locale);
    }

    public function localizedExcerpt(?string $locale = null): string
    {
        return $this->localizedValue('excerpt', $locale);
    }

    public function localizedContent(?string $locale = null): string
    {
        return $this->localizedValue('content', $locale);
    }

    public function localizedSeoTitle(?string $locale = null): string
    {
        return $this->localizedValue('seo_title', $locale) ?: $this->localizedTitle($locale);
    }

    public function localizedSeoDescription(?string $locale = null): string
    {
        return $this->localizedValue('seo_description', $locale) ?: $this->localizedExcerpt($locale);
    }

    private function localizedValue(string $base, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $column = in_array($locale, ['tr', 'de'], true) ? "{$base}_{$locale}" : $base;

        return (string) ($this->{$column} ?: $this->{$base} ?: '');
    }
}
