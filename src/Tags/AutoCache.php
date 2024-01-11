<?php

namespace Tv2regionerne\StatamicCache\Tags;

use Illuminate\Support\Facades\Cache as LaraCache;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Tags\Tags;
use Statamic\View\Antlers\Language\Runtime\LiteralReplacementManager;
use Statamic\View\Antlers\Language\Runtime\StackReplacementManager;
use Statamic\View\State\CachesOutput;

class AutoCache extends Tags implements CachesOutput
{
    public $events = [];

    public function index()
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $store = LaraCache::store();
        $tags = collect($this->params->explode('tags', []));

        // Add entry id as tag
        $tags->add($this->context->get('entry_id')->value());

        $key = $this->getCacheKey();
        $nestedCallsKey = $key.'_sections_stacks';

        if ($cacheTags = LaraCache::get($key.'-tags')) {
            $store = $store->tags($cacheTags);
        }

        if ($cached = $store->get($key)) {
            $nestedResults = $store->get($nestedCallsKey);

            if ($nestedResults != null) {
                StackReplacementManager::restoreCachedStacks($nestedResults['stacks']);
                LiteralReplacementManager::restoreCachedSections($nestedResults['sections']);
            }

            return $cached;
        }

        app('cache-resources')->addWatcher($key);
        $html = (string) $this->parse([]);
        app('cache-resources')->removeWatcher($key);

        $eventTags = app('cache-resources')->cacheTags($key);
        $tags = $tags->concat($eventTags);

        $store = $store->tags($tags->toArray());
        $store->put($key, $html, $this->getCacheLength());
        LaraCache::put($key.'-tags', $tags->toArray());

        $cachedSections = LiteralReplacementManager::getCachedSections();
        $cachedStacks = StackReplacementManager::getCachedStacks();

        if (! empty($cachedSections) || ! empty($cachedStacks)) {
            $nestedCalls = [
                'sections' => LiteralReplacementManager::getCachedSections(),
                'stacks' => StackReplacementManager::getCachedStacks(),
            ];

            $store->put($nestedCallsKey, $nestedCalls, $this->getCacheLength());
        }

        // Do some cleanup so things don't leak elsewhere.
        StackReplacementManager::clearCachedStacks();
        LiteralReplacementManager::clearCachedSections();

        return $html;
    }

    private function isEnabled()
    {
        if (! config('statamic.system.cache_tags_enabled', true)) {
            return false;
        }

        // Only GET requests. This disables the cache during live preview.
        return request()->method() === 'GET';
    }

    private function getCacheKey()
    {
        if ($this->params->has('key')) {
            return $this->params->get('key');
        }

        $hash = [
            'content' => $this->content,
            'params' => $this->params->all(),
        ];

        $scope = $this->params->get('scope', 'site');

        if ($scope === 'site') {
            $hash['site'] = Site::current()->handle();
        }

        if ($scope === 'page') {
            $hash['url'] = URL::makeAbsolute(URL::getCurrent());
        }

        if ($scope === 'user') {
            $hash['user'] = ($user = auth()->user()) ? $user->id : 'guest';
        }

        return 'statamic.cache-tag.'.md5(json_encode($hash));
    }

    private function getCacheLength()
    {
        if (! $length = $this->params->get('for')) {
            return null;
        }

        return now()->add('+'.$length);
    }
}
