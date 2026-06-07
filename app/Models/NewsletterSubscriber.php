<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'newsletter_subscribers';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
