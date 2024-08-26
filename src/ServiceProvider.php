<?php

namespace Tv2regionerne\StatamicCache;

use Statamic\Providers\AddonServiceProvider;
use Tv2regionerne\StatamicCache\Listeners\Subscriber;

class ServiceProvider extends AddonServiceProvider
{
    protected $middlewareGroups = [
        'web' => [
            Http\Middleware\AutoCache::class,
        ],
    ];

    protected $subscribe = [
        Subscriber::class,
    ];

    public function bootAddon()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/statamic-cache.php', 'statamic-cache');

        $this->publishes([
            __DIR__.'/../config/statamic-cache.php' => config_path('statamic-cache.php'),
        ], 'statamic-cache-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
