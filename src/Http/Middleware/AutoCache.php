<?php

namespace Tv2regionerne\StatamicCache\Http\Middleware;

use Closure;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Globals\Variables;
use Statamic\Tags;
use Statamic\Taxonomies\LocalizedTerm;
use Tv2regionerne\StatamicCache\Facades\Store;
use Tv2regionerne\StatamicCache\Tags\Partial;

class AutoCache
{
    public function handle($request, Closure $next)
    {
        if (! $this->isEnabled($request)) {
            return $next($request);
        }

        $this
            ->setupPartialHooks()
            ->setupNavHooks()
            ->setupCollectionHooks()
            ->setupAugmentationHooks();

        return $next($request);
    }

    private function isEnabled($request)
    {
        if (! config('statamic-cache.enabled', true)) {
            return false;
        }

        // Only GET requests. This disables the cache during live preview.
        return $request->method() === 'GET';
    }

    private function setupAugmentationHooks()
    {
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

    private function setupPartialHooks()
    {
        Partial::hook('before-render', function () {
            $key = $this->generateAutoCacheKey();

            if ($cache = Store::getFromCache($key)) {
                return $cache;
            }

            $this->context->put('autocache_key', $key);

            $this->context = $this->context->put('autocache_parents', collect($this->context->get('autocache_parents', []))->push($key)->all());
        });

        Partial::hook('render', function ($html, $next) {
            $html = $next($html);

            if ($key = $this->context->get('autocache_key')) {
                $html = "<!-- {$key} -->\r\n".$html;

                Store::addToCache($key, $html);
            }

            return $html;
        });

        return $this;
    }
}
