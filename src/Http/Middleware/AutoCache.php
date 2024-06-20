<?php

namespace Tv2regionerne\StatamicCache\Http\Middleware;

use Closure;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Globals\Variables;
use Statamic\Tags;
use Statamic\Taxonomies\LocalizedTerm;
use Tv2regionerne\StatamicCache\Facades\Store;

class AutoCache
{
    public function handle($request, Closure $next)
    {
        if (! $this->isEnabled($request)) {
            return $next($request);
        }

        $this
            ->setupNavHooks()
            ->setupCollectionHooks()
            ->setupAugmentationHooks();

        $key = 'default';

        Store::addWatcher($key);

        $response = $next($request);

        Store::removeWatcher($key);

        if (!is_callable([$response, 'wasStaticallyCached'])) {
            return $response;
        }

        if ($response->wasStaticallyCached()) {
            return $response;
        }

        Store::addKeyMappingData($key);

        return $response;
    }

    private function isEnabled($request)
    {
        if (! config('statamic-cache.enabled', true)) {
            return false;
        }

        // Only GET requests. This disables the cache during live preview.
        return $request->method() === 'GET' && substr($request->path(), 0, 2) != '!/';
    }

    private function setupAugmentationHooks()
    {
        app(Asset::class)::hook('augmented', function ($augmented, $next) {
            Store::mergeTags(['asset:'.$this->id()]);

            return $next($augmented);
        });

        app(Entry::class)::hook('augmented', function ($augmented, $next) {
            Store::mergeTags([$this->collection()->handle().':'.$this->id()]);

            return $next($augmented);
        });

        LocalizedTerm::hook('augmented', function ($augmented, $next) {
            Store::mergeTags(['term:'.$this->id()]);

            return $next($augmented);
        });

        app(Variables::class)::hook('augmented', function ($augmented, $next) {
            Store::mergeTags(['global:'.$this->globalSet()->handle()]);

            return $next($augmented);
        });
    }

    private function setupCollectionHooks()
    {
        Tags\Collection\Collection::hook('init', function ($value, $next) {
            $handle = $this->params->get('from') ? 'collection:'.$this->params->get('from') : $this->tag;
            Store::mergeTags([$handle]);

            return $next($value);
        });

        return $this;
    }

    private function setupNavHooks()
    {
        Tags\Nav::hook('init', function ($value, $next) {
            $handle = $this->params->get('handle') ? 'nav:'.$this->params->get('handle') : $this->tag;
            Store::mergeTags([$handle]);

            return $next($value);
        });

        return $this;
    }
}
