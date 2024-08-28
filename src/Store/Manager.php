<?php

namespace Tv2regionerne\StatamicCache\Store;

use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Statamic\Events\UrlInvalidated;
use Statamic\Facades\URL;
use Statamic\StaticCaching\Cacher;
use Statamic\StaticCaching\StaticCacheManager;
use Statamic\Support\Arr;
use Tv2regionerne\StatamicCache\Jobs\InvalidateModel;
use Tv2regionerne\StatamicCache\Models\StaticCache;

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

        [$url, $domain] = $this->splitUrlAndDomain($url);

        if (! empty($this->cacheContent($key))) {
            StaticCache::updateOrCreate([
                'url' => $url,
            ], [
                'key' => md5($url),
                'domain' => $domain ?? '',
                'content' => $this->cacheContent($key),
            ]);
        }

        return $this;
    }

    public function hasMappingData(?string $url = null): bool
    {
        if (! $url) {
            $url = class_exists(Livewire::class) ? Livewire::originalUrl() : URL::getCurrent();
        }

        [$url, $domain] = $this->splitUrlAndDomain($url);

        $model = StaticCache::where(['url' => $url, 'domain' => $domain])->first();

        return $model && $model->content;
    }

    public function invalidateContent($ids): static
    {
        StaticCache::query()
            ->where(function ($query) use ($ids) {
                foreach ($ids as $index => $id) {
                    $query->{($index == 0 ? 'where' : 'orWhere').'JsonContains'}('content', [$id]);
                }
            })
            ->chunk(100, function ($models) {
                $models->each(fn ($model) => InvalidateModel::dispatch($model));
            });

        return $this;
    }

    /* @deprecated - use invalidateModel instead */
    public function invalidateModels($models): void
    {
        $models->each(fn ($model) => $this->invalidateModel($model));
    }

    public function invalidateModel(StaticCache $model): void
    {
        Event::listen(function (UrlInvalidated $event) use ($model) {
            if ($event->url == $model->url) {
                $model->delete();
            }
        });

        $this->invalidateCacheForUrl($model->url);
    }

    public function invalidateCacheForUrl(string $url): void
    {
        $cacher = app(Cacher::class);
        $manager = app()->make(StaticCacheManager::class);
        $cache = $manager->cacheStore();

        [$url, $domain] = $this->splitUrlAndDomain($url);

        $cache->forget('static-cache:responses:'.md5($url));
    }

    private function splitUrlAndDomain(string $url)
    {
        $parsed = parse_url($url);

        if (str_contains($url, '://')) {
            $domain = $parsed['scheme'].'://'.$parsed['host'];
        } else {
            $domain = app(Cacher::class)->getBaseUrl();
        }

        $url = Arr::get($parsed, 'path', '/');

        return [$url, $domain ?? ''];
    }
}
