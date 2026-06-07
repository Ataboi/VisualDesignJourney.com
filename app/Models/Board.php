<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'boards';

    protected $guarded = [];

    protected $casts = [
        'is_draft' => 'boolean',
        'created_at' => 'datetime',
        'save_count' => 'integer',
        'view_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(BoardImage::class)->orderBy('sort_order');
    }

    public function likes()
    {
        return $this->hasMany(BoardLike::class);
    }

    public function saves()
    {
        return $this->hasMany(BoardSave::class);
    }

    public function comments()
    {
        return $this->hasMany(BoardComment::class);
    }
}
