<?php

return [
    'enabled' => env('STATAMIC_AUTOCACHE_ENABLED', true),

    'flush_cache_limit' => env('STATAMIC_AUTOCACHE_FLUSH_LIMIT', 1000),
];
