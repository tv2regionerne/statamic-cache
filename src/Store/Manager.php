<?php

namespace Tv2regionerne\StatamicCache\Store;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Livewire\Livewire;
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
        try {
            $this->store = Cache::store('statamic_autocache');
        } catch (InvalidArgumentException $e) {
            $this->store = Cache::store();
        }

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

    public function cacheContent($key = 'default')
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

    public function addKeyMappingData($key, $parents = [], $expires = null, $tags = [])
    {
        $url = URL::makeAbsolute(class_exists(Livewire::class) ? Livewire::originalUrl() : URL::getCurrent());

        Autocache::updateOrCreate([
            'key' => $key,
            'url' => $url,
        ], [
            'content' => $this->cacheContent($key),
            'parents' => collect($parents)->filter(fn ($value) => $value != $key)->all(),
            'expires_at' => $expires?->timestamp,
            'tags' => implode(',', $tags),
        ]);

        return $this;
    }

    public function removeKeyMappingData($tag)
    {
        Autocache::whereJsonContains('content', [$tag])
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

        return $this;
    }

    public function invalidateCacheKeys($keys)
    {
        $this->invalidateModels(Autocache::whereIn('key', $keys)->get());

        return $this;
    }

    public function invalidateContent($ids)
    {
        $query = Autocache::query()
            ->where(function ($query) use ($ids) {
                foreach ($ids as $index => $id) {
                    $query->{($index == 0 ? 'where' : 'orWhere').'JsonContains'}('content', [$id]);
                }
            });

        $this->invalidateModels($query->get());

        return $this;
    }

    private function invalidateModels($models)
    {
        $models = $models
            ->map(function ($model) {
                // get any children affected by this cache key
                $children = Autocache::whereJsonContains('parents', [$model->key])->get();

                // get any parents affected by this cache key
                $parents = Autocache::whereIn('key', $model->parents)->get();

                return collect([$model])->merge($parents)->merge($children);
            })
            ->flatten()
            ->unique()
            ->each(fn ($model) => $this->store->forget($model->key));

        $models
            ->pluck('url')
            ->unique()
            ->each(fn ($url) => app(Cacher::class)->invalidateUrl($url));

        $models->each->delete();
    }
}
