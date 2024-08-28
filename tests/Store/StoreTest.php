<?php

uses(\Tv2regionerne\StatamicCache\Tests\TestCase::class);

use Illuminate\Support\Facades\Queue;
use Statamic\StaticCaching\Cacher;
use Tv2regionerne\StatamicCache\Facades\Store;
use Tv2regionerne\StatamicCache\Models\StaticCache;

it('it adds tracking data', function () {
    $this->assertCount(0, StaticCache::all());
    $this->assertFalse(Store::hasMappingData('/'));

    Store::addWatcher('default');
    Store::mergeTags(['some:data']);
    Store::addKeyMappingData('default');

    $this->assertCount(1, StaticCache::all());
    $this->assertSame(['some:data'], StaticCache::all()->first()->content);

    $this->assertTrue(Store::hasMappingData('/'));
});

it('it creates an invalidate model job when there is a valid tag', function () {
    Queue::fake();

    Store::addWatcher('default');
    Store::mergeTags(['some:data']);
    Store::addKeyMappingData('default');

    Store::invalidateContent(['some:data']);

    Queue::assertPushed(Tv2regionerne\StatamicCache\Jobs\InvalidateModel::class);
});

it('it doesn\'t an invalidate model job when there is no valid tag', function () {
    Queue::fake();

    Store::addWatcher('default');
    Store::mergeTags(['some:data']);
    Store::addKeyMappingData('default');

    Store::invalidateContent(['someother:data']);

    Queue::assertNotPushed(Tv2regionerne\StatamicCache\Jobs\InvalidateModel::class);
});

it('it flushes the cache when over the config limit', function () {
    config()->set('statamic-cache.flush_cache_limit', 1);
    config()->set('statamic.static_caching.strategy', 'half');
    config()->set('statamic.static_caching.strategies.half.driver', 'redis_with_database');

    Queue::fake();

    StaticCache::insert([
        [
            'key' => md5('/news'),
            'url' => '/news',
            'domain' => 'http://localhost',
            'content' => json_encode(['some:tag']),
        ],
        [
            'key' => md5('/news/two'),
            'url' => '/news/two',
            'domain' => 'http://localhost',
            'content' => json_encode(['some:tag']),
        ],
        [
            'key' => md5('/news/three'),
            'url' => '/news/three',
            'domain' => 'http://localhost',
            'content' => json_encode(['other:tag']),
        ],
    ]);

    Store::invalidateContent(['some:tag']);

    Queue::assertNotPushed(Tv2regionerne\StatamicCache\Jobs\InvalidateModel::class);

    $this->assertCount(0, StaticCache::all());
});
