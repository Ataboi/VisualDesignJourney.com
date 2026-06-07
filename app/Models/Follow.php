<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'follows';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function following()
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}
