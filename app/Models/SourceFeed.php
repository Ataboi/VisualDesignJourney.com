<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceFeed extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'source_feeds';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
