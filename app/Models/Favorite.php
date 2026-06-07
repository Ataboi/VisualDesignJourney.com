<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = ['session_id', 'user_id', 'favorable_type', 'favorable_id'];

    public function favorable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
