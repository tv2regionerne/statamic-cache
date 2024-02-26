<?php

namespace Tv2regionerne\StatamicCache;

use Statamic\Providers\AddonServiceProvider;
use Tv2regionerne\StatamicCache\Listeners\Subscriber;

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
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        $this->rebindPartialTag();
    }
    
    private function rebindPartialTag()
    {
        $extensions = app('statamic.extensions');
        $key = 'Statamic\\Tags\\Tags';

        $extensions[$key] = with($extensions[$key] ?? collect(), function ($bindings) {
            $bindings['partial'] = Tags\Partial::class;
                
            return $bindings;
        });
        
        return $this;
    }
}
