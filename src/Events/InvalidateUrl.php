<?php

namespace Tv2regionerne\StatamicCache\Events;

use Statamic\Events\Event;

class InvalidateUrl extends Event
{
    public function __construct(public string $url)
    {
    }
}
