<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardImage extends Model
{
    public $timestamps = false;

    protected $table = 'board_images';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
