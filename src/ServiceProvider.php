<?php

namespace Tv2regionerne\StatamicCache;

use Statamic\Facades\URL;
use Statamic\Providers\AddonServiceProvider;
use Tv2regionerne\StatamicCache\Facades\Store;
use Tv2regionerne\StatamicCache\Listeners\Subscriber;
use Tv2regionerne\StatamicCache\Tags\Partial;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\AutoCache::class,
    ];

    protected $subscribe = [
        Subscriber::class,
    ];

    public function register()
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

            $key = 'autocache::'.md5(URL::makeAbsolute(URL::getCurrent())).':'.$depth.':'.str_replace('/', '.', $src);

            if ($prefix = $this->params->get('prefix') ? $prefix.'__' : '') {
                $key = $prefix.$key;
            }

            if ($cache = Store::getFromCache($key)) {
                return $cache;
            }

            $this->params->put('autocache_key', $key);

            // this could probably be handled in a store?
            $parents = collect($this->context->get('autocache_parents', []))->push($key)->all();
            $this->context = $this->context->put('autocache_parents', $parents);
        });

        Partial::hook('render', function ($html, $next) {
            $html = $next($html);

            if ($key = $this->params->get('autocache_key')) {
                $html = "<!-- {$key} -->\r\n".$html;

                Store::addToCache($key, $html);
            }

            return $html;
        });
    }

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
