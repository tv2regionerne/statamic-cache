<?php

namespace Tv2regionerne\StatamicCache\Tags;

use Statamic\Facades\URL;
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
