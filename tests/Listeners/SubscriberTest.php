<?php

uses(\Tv2regionerne\StatamicCache\Tests\TestCase::class);

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades;
use Tv2regionerne\StatamicCache\Facades\Store;
use Tv2regionerne\StatamicCache\Jobs\Invalidate;
use Tv2regionerne\StatamicCache\Listeners\Subscriber;
use Tv2regionerne\StatamicCache\Models\StaticCache;

it('it invalidates entry tags', function () {
    Queue::fake();

    Facades\Collection::make('test')->save();
    $entry = tap(Facades\Entry::make()->id('test')->collection('test'))->save();

    Store::addWatcher('default');
    Store::mergeTags(['test:test']);
    Store::addKeyMappingData('default');

    $this->assertCount(1, StaticCache::whereJsonContains('content', ['test:test'])->get());

    $event = new stdClass;
    $event->entry = $entry;

    (new Subscriber)->invalidateEntry($event);

    Queue::assertPushed(function (Invalidate $job) {
        return $job->tags == ['test:test', 'collection:test'];
    });
});

it('it invalidates asset tags', function () {
    Queue::fake();
    Storage::fake('test');

    $container = tap(Facades\AssetContainer::make('test')->disk('test'))->save();

    $asset = $container->makeAsset('foo/image_in_short.jpg');
    $asset->save();

    Store::addWatcher('default');
    Store::mergeTags(['asset:test::foo/image_in_short.jpg']);
    Store::addKeyMappingData('default');

    $this->assertCount(1, StaticCache::whereJsonContains('content', ['asset:test::foo/image_in_short.jpg'])->get());

    $event = new stdClass;
    $event->asset = $asset;

    (new Subscriber)->invalidateAsset($event);

    Queue::assertPushed(function (Invalidate $job) {
        return $job->tags == ['asset:test::foo/image_in_short.jpg'];
    });
});

it('it invalidates global tags', function () {
    Queue::fake();

    $global = tap(Facades\GlobalSet::make('test'))->save();

    Store::addWatcher('default');
    Store::mergeTags(['global:test']);
    Store::addKeyMappingData('default');

    $this->assertCount(1, StaticCache::whereJsonContains('content', ['global:test'])->get());

    $event = new stdClass;
    $event->globals = $global;

    (new Subscriber)->invalidateGlobal($event);

    Queue::assertPushed(function (Invalidate $job) {
        return $job->tags == ['global:test'];
    });
});

it('it invalidates nav tags', function () {
    Queue::fake();

    $nav = tap(Facades\Nav::make('test'))->save();

    Store::addWatcher('default');
    Store::mergeTags(['nav:test']);
    Store::addKeyMappingData('default');

    $this->assertCount(1, StaticCache::whereJsonContains('content', ['nav:test'])->get());

    $event = new stdClass;
    $event->nav = $nav;

    (new Subscriber)->invalidateNav($event);

    Queue::assertPushed(function (Invalidate $job) {
        return $job->tags == ['nav:test'];
    });
});

it('it invalidates term tags', function () {
    Queue::fake();

    Facades\Taxonomy::make('test')->save();

    $term = Facades\Term::make('test')->taxonomy('test');
    $term->in('default')->slug('test')->set('title', 'Test');
    $term->save();

    Store::addWatcher('default');
    Store::mergeTags(['term:test::test']);
    Store::addKeyMappingData('default');

    $this->assertCount(1, StaticCache::whereJsonContains('content', ['term:test::test'])->get());

    $event = new stdClass;
    $event->term = $term;

    (new Subscriber)->invalidateTerm($event);

    Queue::assertPushed(function (Invalidate $job) {
        return $job->tags == ['term:test::test'];
    });
});
