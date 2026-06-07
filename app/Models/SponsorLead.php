<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorLead extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'sponsor_leads';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
