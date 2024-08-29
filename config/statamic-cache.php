<?php

return [
    'enabled' => env('STATAMIC_AUTOCACHE_ENABLED', true),

    /**
     * Check if a database entry exists for the page when resolved from cache.
     * Ensure consistency between cache and database, but requires each request to do a db query
     */
    'split_brain_check' => env('STATAMIC_AUTOCACHE_SPLITBRAIN_CHECK', false),

    'flush_cache_limit' => env('STATAMIC_AUTOCACHE_FLUSH_LIMIT', 1000),
];
