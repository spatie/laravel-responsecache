<?php

namespace Spatie\ResponseCache\Commands;

use Illuminate\Console\Command;
use Illuminate\Cache\CacheManager;
use Spatie\ResponseCache\Events\FlushedResponseCache;
use Spatie\ResponseCache\Events\FlushingResponseCache;

class Flush extends Command
{
    protected $signature = 'responsecache:flush';

    protected $description = 'Flush the response cache';

    public function handle()
    {
        $storeName = config('responsecache.cache_store');

        event(new FlushingResponseCache());

        app(CacheManager::class)->store($storeName)->flush();

        event(new FlushedResponseCache());

        $this->info('Response cache flushed!');
    }
}
