<?php

uses(\Tv2regionerne\StatamicCache\Tests\TestCase::class);

use Illuminate\Support\Facades\Event;
use Statamic\Events\UrlInvalidated;
use Statamic\StaticCaching\Cacher;
use Tv2regionerne\StatamicCache\Models\StaticCache;

beforeEach(function () {
    config()->set('statamic.static_caching.strategy', 'half');
    config()->set('statamic.static_caching.strategies.half.driver', 'redis_with_database');
});

it('caches urls', function () {
    $this->assertCount(0, StaticCache::all());

    $this->get('/');

    $this->assertCount(1, StaticCache::all());

    $model = StaticCache::first();

    $this->assertSame($model->url, '/');
    $this->assertSame($model->domain, 'http://localhost');
});

it('invalidates urls', function () {
    Event::fake();

    $this->get('/');

    $this->assertCount(1, StaticCache::all());

    app(Cacher::class)->invalidateUrl('/');

    Event::assertDispatched(UrlInvalidated::class);

    $this->assertCount(0, StaticCache::all());
});

it('invalidates wildcard urls', function () {
    Event::fake();

    StaticCache::insert([
        [
            'key' => md5('/news'),
            'url' => '/news',
            'domain' => 'http://localhost',
        ],
        [
            'key' => md5('/news/two'),
            'url' => '/news/two',
            'domain' => 'http://localhost',
        ],
        [
            'key' => md5('/home'),
            'url' => '/home',
            'domain' => 'http://localhost',
        ],
    ]);

    $this->assertCount(3, StaticCache::all());

    app(Cacher::class)->invalidateUrls(['/news*']);

    Event::assertDispatched(UrlInvalidated::class);

    $this->assertCount(1, StaticCache::all());
});

it('flushes the cache', function () {
    Event::fake();

    StaticCache::insert([
        [
            'key' => md5('/news'),
            'url' => '/news',
            'domain' => 'http://localhost',
        ],
        [
            'key' => md5('/news/two'),
            'url' => '/news/two',
            'domain' => 'http://localhost',
        ],
    ]);

    $this->assertCount(2, StaticCache::all());

    app(Cacher::class)->flush();

    $this->assertCount(0, StaticCache::all());
});

it('sets headers from config', function () {
    config()->set('statamic-cache.headers', [
        'hit' => [
            'x-statamic-cache' => 'hit',
            'cache.headers' => 'public;max_age=1628000;etag', // will pass to laravel's middleware
        ],

        'miss' => [
            'x-statamic-cache' => 'miss',
            'cache.headers' => 'public;max_age=2628000;etag', // will pass to laravel's middleware
        ],

        'not-available' => [
            'x-statamic-cache' => 'not-available',
            'cache.headers' => 'public;max_age=3628000;etag', // will pass to laravel's middleware
        ],
    ]);

    $response = $this->get('/');

    $this->assertSame($response->headers->get('cache-control'), 'max-age=2628000, no-cache, public');
    $this->assertSame($response->headers->get('x-statamic-cache'), 'miss');

    $response = $this->get('/');

    $this->assertSame($response->headers->get('cache-control'), 'max-age=1628000, public');
    $this->assertSame($response->headers->get('x-statamic-cache'), 'hit');
});
