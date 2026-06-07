<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BriefTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'industry', 'description', 'fields', 'sample_output',
        'category_id', 'is_featured', 'is_active', 'use_count',
    ];

    protected $casts = [
        'fields' => 'array',
        'sample_output' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
