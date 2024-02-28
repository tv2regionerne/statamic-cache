<?php

namespace Tv2regionerne\StatamicCache\Listeners;

use Illuminate\Cache\Events\KeyForgotten;
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

        KeyForgotten::class => 'removeAutocacheModels',
    ];

    public function subscribe($dispatcher): void
    {
        foreach ($this->events as $event => $method) {
            if (class_exists($event)) {
                $dispatcher->listen($event, [self::class, $method]);
            }
        }

    }

    public function registerEntry($event): void
    {
        if (! $event->entry) {
            return;
        }

        Store::mergeEntries($event->entry);
    }

    public function registerCollection($event): void
    {
        $tag = 'collection:'.$event->tag->params->get('from');

        Store::mergeTags($tag);
    }

    public function registerCuratedCollection($event): void
    {
        $tag = 'curated-collection:'.strtolower($event->tag);

        Store::mergeTags($tag);
    }

    public function invalidateCuratedCollections(CuratedCollectionUpdatedEvent $event)
    {
        $tags = ['curated-collection:'.$event->tag];

        Store::invalidateContent($tags);
    }

    public function invalidateEntry($event)
    {
        $entry = $event->entry;

        $collectionHandle = strtolower($entry->collection()->handle());

        $tags = [
            $collectionHandle.':'.$entry->id(),
            'collection:'.$collectionHandle,
        ];

        Store::invalidateContent($tags);
    }

    public function removeAutocacheModels($key)
    {
        Store::removeKeyMappingData($key);
    }
}
