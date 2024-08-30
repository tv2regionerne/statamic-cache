<?php

return [
    'enabled' => env('STATAMIC_AUTOCACHE_ENABLED', true),

    /**
     * Set the threshold at which we just flush the cache rather than
     * invalidating individual pages
     */
    'flush_cache_limit' => env('STATAMIC_AUTOCACHE_FLUSH_LIMIT', 1000),

    /**
     * Determine what headers will be set on responses depending
     * on whether they were pulled from the cache or not
     */
    'headers' => [
        'hit' => [
            'x-statamic-cache' => 'hit',
            //'cache.headers' => 'no-cache,private,max-age=1200;etag', // will pass to laravel's middleware
        ],

        'miss' => [
            'x-statamic-cache' => 'miss',
            //'cache.headers' => 'no-cache,private,max-age=900;etag', // will pass to laravel's middleware
        ],

        'not-available' => [
            'x-statamic-cache' => 'not-available',
            //'cache.headers' => 'no-cache,private,max-age=900;etag', // will pass to laravel's middleware
        ],
    ],

    /**
     * Check if a database entry exists for the page when resolved from cache.
     * Ensure consistency between cache and database, but requires each request to do a db query
     */
    'split_brain_check' => env('STATAMIC_AUTOCACHE_SPLITBRAIN_CHECK', false),
];
