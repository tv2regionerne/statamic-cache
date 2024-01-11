<?php

namespace Tv2regionerne\StatamicCache;

use Statamic\Providers\AddonServiceProvider;
use Tv2regionerne\StatamicCache\Listeners\Subscriber;
use Tv2regionerne\StatamicCache\Store\Resource;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\AutoCache::class,
    ];

    protected $subscribe = [
        Subscriber::class,
    ];

    public function bootAddon()
    {
        //
    }

    public function register()
    {
        $this->app->singleton('cache-resources', function () {
            return new Resource();
        });
    }
}
