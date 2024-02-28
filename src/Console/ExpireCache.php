<?php

namespace Tv2regionerne\StatamicCache\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Tv2regionerne\StatamicCache\Models\Autocache;

class ExpireCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic-cache:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire any autocache partials';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Expire caches...');
        $this->line('');

        $keys = Autocache::whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now()->timestamp)
            ->get()
            ->pluck('key')
            ->values()
            ->all();

        if (! empty($tags)) {
            Store::invalidateKeys($keys);
        }

        $this->info('✔️ Done');

        return 1;
    }
}
