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
        app(Asset::class)::hook('augmented', function () {
            Store::mergeTags(['asset:'.$this->id()]);
        });

        app(Entry::class)::hook('augmented', function () {
            Store::mergeTags([$this->collection()->handle().':'.$this->id()]);
        });

        LocalizedTerm::hook('augmented', function () {
            Store::mergeTags(['term:'.$this->id()]);
        });

        app(Variables::class)::hook('augmented', function () {
            Store::mergeTags(['global:'.$this->globalSet()->handle()]);
        });
    }

    private function setupCollectionHooks()
    {
        Tags\Collection\Collection::hook('init', function () {
            $handle = $this->params->get('from') ? 'collection:'.$this->params->get('from') : $this->tag;
            Store::mergeTags([$handle]);
        });

        return $this;
    }

    private function setupNavHooks()
    {
        Tags\Nav::hook('init', function () {
            $handle = $this->params->get('handle') ? 'nav:'.$this->params->get('handle') : $this->tag;
            Store::mergeTags([$handle]);
        });

        return $this;
    }
}
