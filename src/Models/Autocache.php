<?php

namespace Tv2regionerne\StatamicCache\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Autocache extends Model
{
    use HasUuids;

    public $table = 'autocache';

    protected $casts = [
        'tags' => 'array',
        'parents' => 'array',
    ];

    protected $guarded = [];
}
