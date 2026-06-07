<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceBookmark extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'source_bookmarks';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
