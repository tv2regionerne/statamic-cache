<?php

namespace Tv2regionerne\StatamicCache\Models;

use Illuminate\Database\Eloquent\Model;

class StaticCache extends Model
{
    public $timestamps = false;

    protected $table = 'static_cache';

    protected $guarded = [];

    protected function casts()
    {
        return [
            'headers' => 'array',
        ];
    }
}
