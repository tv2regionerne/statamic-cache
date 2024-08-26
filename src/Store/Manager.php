<?php

namespace Tv2regionerne\StatamicCache\Store;

use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Statamic\Events\UrlInvalidated;
use Statamic\Facades\URL;
use Statamic\StaticCaching\Cacher;
use Statamic\StaticCaching\StaticCacheManager;
use Statamic\Support\Arr;
use Tv2regionerne\StatamicCache\Jobs\InvalidateAutoCacheChunk;
use Tv2regionerne\StatamicCache\Models\Autocache;

class Manager
{
    protected array $entries;

    protected $store;

    protected array $tags = [];

    protected array $watchers = [];

    public function __construct()
    {
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

    public function cacheContent($key = 'default'): array
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

    public function mergeEntries($entries): static
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

    public function mergeTags($tags): static
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

    public function addKeyMappingData($key): static
    {
        $url = class_exists(Livewire::class) ? Livewire::originalUrl() : URL::getCurrent();

        if (! empty($this->cacheContent($key))) {
            Autocache::updateOrCreate([
                'url' => $url,
            ], [
                'content' => $this->cacheContent($key),
            ]);
        }

        return $this;
    }

    public function getMappingData(?string $url = null): bool
    {
        if (! $url) {
            $url = class_exists(Livewire::class) ? Livewire::originalUrl() : URL::getCurrent();
        }

        return Autocache::where('url', $url)->get();
    }

    public function invalidateContent($ids): static
    {
        $query = Autocache::query()
            ->where(function ($query) use ($ids) {
                foreach ($ids as $index => $id) {
                    $query->{($index == 0 ? 'where' : 'orWhere').'JsonContains'}('content', [$id]);
                }
            });
        $query->chunk(100, function ($models) {
            InvalidateAutoCacheChunk::dispatch($models);
        });

        return $this;
    }

    public function invalidateModels($models): void
    {
        $models->each(function (Autocache $model) {
            Event::listen(function (UrlInvalidated $event) use ($model) {
                if ($event->url == $model->url) {
                    $model->delete();
                }
            });

            $this->invalidateCacheForUrl($model->url);
        });
    }

    public function invalidateCacheForUrl(string $url): void
    {
        $cacher = app(Cacher::class);
        $manager = app()->make(StaticCacheManager::class);
        $cache = $manager->cacheStore();

        $parsed = parse_url($url);

        $cacher->invalidateUrl(Arr::get($parsed, 'path', '/'));
        $cache->forget('static-cache:responses:'.md5($url));
    }
}
