<?php

namespace Tv2regionerne\StatamicCache\Http\Middleware;

use Closure;
use Livewire\Livewire;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Tags;
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
        if (! config('statamic.system.cache_tags_enabled', true)) {
            return false;
        }

        // Only GET requests. This disables the cache during live preview.
        return $request->method() === 'GET';
    }

    private function setupAugmentationHooks()
    {
        \Statamic\Entries\Entry::hook('augmented', function () {
            Store::mergeTags([$this->collection()->handle().':'.$this->id()]);
        });

        if (class_exists(\Statamic\Eloquent\Entries\Entry::class)) {
            \Statamic\Eloquent\Entries\Entry::hook('augmented', function () {
                Store::mergeTags([$this->collection()->handle().':'.$this->id()]);
            });
        }

        \Statamic\Globals\Variables::hook('augmented', function () {
            Store::mergeTags(['global:'.$this->globalSet()->handle()]);
        });

        if (class_exists(\Statamic\Eloquent\Globals\Variables::class)) {
            \Statamic\Eloquent\Globals\Variables::hook('augmented', function () {
                Store::mergeTags(['global:'.$this->globalSet()->handle()]);
            });
        }
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
            $src = $this->params->get('src') ?? str_replace('partial:', '', $this->tag);

            // get depth of stack
            $parser = new \ReflectionObject($this->parser);
            $depth = $parser->getProperty('parseStack')->getValue($this->parser);

            // if we are looping
            if ($count = $this->context->int('count')) {
                $depth .= ':'.$count;
            }

            $scope = $this->params->get('scope', 'page');

            if ($scope === 'site') {
                $hash = Site::current()->handle();
            }

            if ($scope === 'page') {
                $hash = URL::makeAbsolute(class_exists(Livewire::class) ? Livewire::originalUrl() : URL::getCurrent());
            }

            if ($scope === 'user') {
                $hash = ($user = auth()->user()) ? $user->id : 'guest';
            }

            $key = 'autocache::'.md5($hash).':'.$depth.':'.str_replace('/', '.', $src);

            if ($prefix = $this->params->get('prefix') ? $prefix.'__' : '') {
                $key = $prefix.$key;
            }

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
