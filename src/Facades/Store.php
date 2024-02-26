<?php

namespace Tv2regionerne\StatamicCache\Facades;

use Illuminate\Support\Facades\Facade;
use Tv2regionerne\StatamicCache\Store\Manager;

class Store extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return Manager::class;
    }
}
