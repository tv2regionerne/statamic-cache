<?php

namespace Tv2regionerne\StatamicCache\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tv2regionerne\StatamicCache\Facades\Store;

class InvalidateModel implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $model)
    {
    }

    public function tags(): array
    {
        return [
            'url:'.$this->model->url,
            'id:'.$this->model->id
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Store::invalidateModel($this->model);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return md5($this->model->url);
    }
}
