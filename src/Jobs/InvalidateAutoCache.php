<?php

namespace Tv2regionerne\StatamicCache\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tv2regionerne\StatamicCache\Facades\Store;

class InvalidateAutoCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $tags)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Store::invalidateContent($this->tags);
    }
}
