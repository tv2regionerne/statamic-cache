<?php

namespace Tv2regionerne\StatamicCache\Store;

use Illuminate\Support\Facades\Cache as LaraCache;

class Resource
{
    protected array $entries;
    
    protected $store;

    protected array $tags = [];

    protected array $watchers = [];

    public function __construct()
    {
        $this->store = LaraCache::store();
        $this->entries = [];
        $this->tags = [];
        $this->watchers = ['default'];
    }

    public function addWatcher(string $key): self
    {
        $this->watchers[$key] = $key;

        return $this;
    }

    public function removeWatcher(string $key): self
    {
        if ($key === 'default') {
            return $this;
        }
        unset($this->watchers[$key]);

        return $this;
    }

    public function entries($key = 'default')
    {
        return $this->entries[$key] ?? [];
    }

    public function tags($key = 'default')
    {
        return $this->tags[$key] ?? [];
    }

    public function cacheTags($key = 'default')
    {
        $entryTags = collect($this->entries($key))->transform(function ($entry) {
            return strtolower($entry->collection()->handle()).':'.$entry->id();
        })->unique()->toArray();
        $tags = collect($this->tags($key))->unique()->toArray();

        return array_merge($entryTags, $tags);
    }

    public function mergeEntries($entries)
    {
        if (! is_array($entries)) {
            $entries = [$entries];
        }

        foreach ($this->watchers as $watcher) {
            if (! array_key_exists($watcher, $this->entries)) {
                $this->entries[$watcher] = [];
            }
            $this->entries[$watcher] = array_merge($this->entries[$watcher], $entries);
        }

        return $this;
    }

    public function mergeTags($tags)
    {
        if (! is_array($tags)) {
            $tags = [$tags];
        }

        foreach ($this->watchers as $watcher) {
            if (! array_key_exists($watcher, $this->tags)) {
                $this->tags[$watcher] = [];
            }
            $this->tags[$watcher] = array_merge($this->tags[$watcher], $tags);
        }

        return $this;
    }
    
    public function cache()
    {
        return $this->store;
    }
    
    public function getFromCache($key)
    {
        return $this->store->get($key);
    }
    
    public function addToCache($key, $value)
    {
        return $this->store->forever($key, $value); 
    }
    
    public function addKeyMapping($key)
    {
        if ($tags = $this->cacheTags($key)) {
            $this->addToCache(str_replace('autocache::partial:', 'autocache::mapping:', $key), $tags);  
        }    
        
        return $this;
    }
}
