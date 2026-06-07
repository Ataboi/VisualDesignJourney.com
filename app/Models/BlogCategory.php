<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    public $timestamps = false;

    protected $table = 'blog_categories';

    protected $guarded = [];

    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_categories', 'category_id', 'post_id');
    }
}
