<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogTag extends Model
{
    public $timestamps = false;

    protected $table = 'blog_tags';

    protected $guarded = [];

    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tags', 'tag_id', 'post_id');
    }
}
