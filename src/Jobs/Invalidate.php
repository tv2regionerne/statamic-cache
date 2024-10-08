<?php

namespace Tv2regionerne\StatamicCache\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tv2regionerne\StatamicCache\Facades\Store;

class Invalidate implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $tags)
    {
        asort($this->tags);
    }

    public function tags(): array
    {
        return collect($this->tags)->unique()->transform(function ($tag) {
            return 'tag:'.$tag;
        })->toArray();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Store::invalidateContent($this->tags);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return md5(json_encode($this->tags));
    }
}
