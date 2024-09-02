<?php

namespace Tv2regionerne\StatamicCache\Events;

use Statamic\Events\Event;

class InvalidateUrls extends Event
{
    public function __construct(public $urls)
    {
    }
}
