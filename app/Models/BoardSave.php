<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardSave extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'board_saves';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
