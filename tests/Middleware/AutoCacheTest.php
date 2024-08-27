<?php

uses(\Tv2regionerne\StatamicCache\Tests\TestCase::class);

use Illuminate\Http\Request;
use Tv2regionerne\StatamicCache\Facades\Store;
use Tv2regionerne\StatamicCache\Http\Middleware\AutoCache;

it('it adds tracking data during the request lifecycle', function () {
    $this->assertFalse(Store::hasMappingData('/'));

    $request = Request::create('/');

    $next = function () {
        Store::mergeTags(['some:thing']);

        return response('');
    };

    $middleware = new AutoCache();
    $middleware->handle($request, $next);

    $this->assertTrue(Store::hasMappingData('/'));
});

it('it doesn\'t add tracking data when page is already cached', function () {
    $this->assertTrue(false);
});

it('it invalidates the cache when the store has no content', function () {
    $this->assertTrue(false);
});
