<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VibeStyle extends Model
{
    public $timestamps = false;

    protected $table = 'vibe_styles';

    protected $guarded = [];

    protected $casts = [
        'palette_colors' => 'array',
    ];
}
