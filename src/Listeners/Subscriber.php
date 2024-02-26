<?php

namespace Tv2regionerne\StatamicCache\Listeners;

use Illuminate\Support\Facades\Cache;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Tv2regionerne\StatamicCache\Facades\Store;
use Tv2regionerne\StatamicCuratedCollection\Events\CuratedCollectionTagEvent;
use Tv2regionerne\StatamicCuratedCollection\Events\CuratedCollectionUpdatedEvent;
use Tv2regionerne\StatamicDeduplicate\Events\CollectionTagEvent;

class Subscriber
{
    protected $events = [
        EntryBlueprintFound::class => 'registerEntry',
        CollectionTagEvent::class => 'registerCollection',
        CuratedCollectionTagEvent::class => 'registerCuratedCollection',
        EntrySaved::class => 'invalidateEntry',
        EntryDeleted::class => 'invalidateEntry',
        CuratedCollectionUpdatedEvent::class => 'invalidateCuratedCollections',
    ];

    public function subscribe($dispatcher): void
    {
        foreach ($this->events as $event => $method) {
            if (class_exists($event)) {
                $dispatcher->listen($event, self::class.'@'.$method);
            }
        }
    }

    public function registerEntry($event): void
    {
        if ($event->entry) {
            Store::mergeEntries($event->entry);
        }
    }

    public function registerCollection($event): void
    {
        $collection = $event->tag->params->get('from');
        $tag = 'collection:'.$collection;
        Store::mergeTags($tag);
    }

    public function registerCuratedCollection($event): void
    {
        $handle = $event->tag;
        $tag = 'curated-collection:'.strtolower($handle);
        Store::mergeTags($tag);
    }

    public function invalidateCuratedCollections(CuratedCollectionUpdatedEvent $event)
    {
        $handle = $event->tag;
        $tags = ['curated-collection:'.$handle];
        Cache::tags($tags)->flush();
    }

    public function invalidateEntry($event)
    {

        $entry = $event->entry;

        $collectionHandle = strtolower($entry->collection()->handle());

        $tags = [
            $collectionHandle.':'.$entry->id(),
            'collection:'.$collectionHandle,
        ];

        Cache::tags($tags)->flush();
    }
}
