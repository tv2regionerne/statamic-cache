<?php

namespace Tv2regionerne\StatamicCache\Cacher;

use Statamic\Events\UrlInvalidated;
use Statamic\StaticCaching\Cachers\ApplicationCacher;
use Tv2regionerne\StatamicCache\Models\StaticCache;

class Cacher extends ApplicationCacher
{
    public function cacheUrl($key, $url, $domain = null)
    {
        $domain = $domain ?: $this->getBaseUrl();

        StaticCache::updateOrCreate([
            'url' => $url,
        ], [
            'key' => $key,
            'domain' => $domain,
        ]);
    }

    public function forgetUrl($key, $domain = null)
    {
        $domain = $domain ?: $this->getBaseUrl();

        StaticCache::where(['key' => $key, 'domain' => $domain])->delete();
    }

    public function invalidateUrl($url, $domain = null)
    {
        $domain = $domain ?: $this->getBaseUrl();

        StaticCache::query()
            ->where('domain', $domain)
            ->where(fn ($q) => $q
                ->where('url', $url)
                ->orWhere('url', 'like', $url.'?%'))
            ->get()
            ->each(function ($model) {
                $this->cache->forget($this->normalizeKey('responses:'.$model->key));
                $model->delete();
            });

        UrlInvalidated::dispatch($url, $domain);
    }

    protected function invalidateWildcardUrl($wildcard)
    {
        // Remove the asterisk
        $wildcard = substr($wildcard, 0, -1);

        [$wildcard, $domain] = $this->getPathAndDomain($wildcard);

        StaticCache::query()
            ->where('domain', $domain)
            ->where('url', 'like', $wildcard.'%')
            ->each(fn ($model) => $this->invalidateUrl($model->url, $domain));
    }

    public function flush()
    {
        StaticCache::query()->truncate();

        $this->cache->flush();
    }
}
