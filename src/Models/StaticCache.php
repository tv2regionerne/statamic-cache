<?php

namespace Tv2regionerne\StatamicCache\Models;

use Illuminate\Database\Eloquent\Model;

class StaticCache extends Model
{
    protected $casts = [
        'content' => 'array',
    ];

    protected $guarded = [];

    protected $table = 'static_cache';
}
