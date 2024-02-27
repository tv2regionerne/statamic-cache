<?php

namespace Tv2regionerne\StatamicCache\Store;

use Illuminate\Support\Facades\Cache as LaraCache;
use Statamic\Facades\URL;
use Statamic\StaticCaching\Cacher;
use Tv2regionerne\StatamicCache\Models\Autocache;

class Manager
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
        $entryTags = collect($this->entries($key))
            ->transform(function ($entry) {
                return strtolower($entry->collection()->handle()).':'.$entry->id();
            })
            ->unique()
            ->toArray();

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

    public function addKeyMappingData($key, $parents = [])
    {
        Autocache::updateOrCreate([
            'key' => $key,
            'url' => URL::makeAbsolute(URL::getCurrent()),
        ], [
            'tags' => $this->cacheTags($key),
            'parents' => collect($parents)->filter(fn ($value) => $value != $key)->all(),
        ]);

        return $this;
    }

    public function removeKeyMappingData($tag)
    {
        Autocache::whereJsonContains('tags', [$tag])
            ->get()
            ->map(function ($model) {
                // get any children affected by this cache key
                $children = Autocache::whereJsonContains('parents', [$model->key])->get();

                // get any parents affected by this cache key
                $parents = Autocache::whereIn('key', $model->parents)->get();

                return collect([$model])->merge($parents)->merge($children);
            })
            ->flatten()
            ->unique()
            ->each(fn ($model) => $model->delete());
    }

    public function invalidateTags($tags)
    {

        foreach ($tags as $tag) {
            Autocache::whereJsonContains('tags', $tag)
                ->get()
                ->map(function ($model) {
                    // get any children affected by this cache key
                    $children = Autocache::whereJsonContains('parents', $model->key)->get();

                    // get any parents affected by this cache key
                    $parents = Autocache::whereIn('key', $model->parents)->get();

                    return collect([$model])->merge($parents)->merge($children);
                })
                ->flatten()
                ->unique()
                ->each(fn ($model) => $this->invalidateModel($model));
        }
    }

    public function invalidateModel(Autocache $autocache) {
        ray($autocache);
        $this->store->forget($autocache->key);
        app(Cacher::class)->invalidateUrl($autocache->url);
    }
}
