<?php

namespace Tv2regionerne\StatamicCache\Tags;

use Statamic\Tags;
use Statamic\View\State\CachesOutput;
use Tv2regionerne\StatamicCache\Facades\Store;

class AutoCache extends Tags\Tags implements CachesOutput
{
    public $events = [];

    public function index()
    {
        if (! $this->isEnabled()) {
            return [];
        }

        Tags\Partial::hook('before-render', function () {
            $src = $this->params->get('src') ?? str_replace('partial:', '', $this->tag);
            
            // get depth of stack
            $parser = new \ReflectionObject($this->parser);
            $depth = $parser->getProperty('parseStack')->getValue($this->parser);
            
            // if we are looping
            if ($count = $this->context->int('count')) {
                $depth .= ':'.$count;
            }
            
            $key = ($prefix = $this->params->get('prefix') ? $prefix.'__' : '').'autocache::partial:'.$depth.':'.str_replace('/', ':', $src);
            
            if ($cache = Store::getFromCache($key)) {
                return $cache;
            }
            
            $this->params->put('autocache_key', $key);
            
            // this could probably be handled in a store?
            $parents = collect($this->context->get('autocache_parents', []))->push($key)->all();
            $this->context->put('autocache_parents', $parents);            
        });
        
        Tags\Partial::hook('render', function ($html, $next) {
            $html = $next($html);
            
            if ($key = $this->params->get('autocache_key')) {
                $html = "<!-- {$key} -->\r\n".$html;
                
                Store::addToCache($key, $html);  
            }
            
            if ($parents = $this->params->get('autocache_parents')) {
                Store::addToCache(str_replace('autocache::partial:', 'autocache::parents:', $key), $parents);  
            }            
            
            return $html;
        });

        return (string) $this->parse([]);
    }

    private function isEnabled()
    {
        if (! config('statamic.system.cache_tags_enabled', true)) {
            return false;
        }

        // Only GET requests. This disables the cache during live preview.
        return request()->method() === 'GET';
    }
}
