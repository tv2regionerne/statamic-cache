<?php

namespace Tv2regionerne\StatamicCache;

use Illuminate\Console\Scheduling\Schedule;
use Statamic\Providers\AddonServiceProvider;
use Tv2regionerne\StatamicCache\Listeners\Subscriber;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        Console\ExpireCache::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            Http\Middleware\Autocache::class,
        ],
    ];

    protected $subscribe = [
        Subscriber::class,
    ];

    public function bootAddon()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->rebindPartialTag()
            ->bootScheduledTasks();
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

    private function bootScheduledTasks()
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('statamic-cache:expire')->everyMinute();
        });

        return $this;
    }
}
