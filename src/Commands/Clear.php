<?php

namespace Spatie\ResponseCache\Commands;

use Illuminate\Console\Command;
use Spatie\ResponseCache\ResponseCacheRepository;
use Spatie\ResponseCache\Events\ClearedResponseCache;
use Spatie\ResponseCache\Events\FlushedResponseCache;
use Spatie\ResponseCache\Events\ClearingResponseCache;
use Spatie\ResponseCache\Events\FlushingResponseCache;

class Clear extends Command
{
    protected $signature = 'responsecache:clear';

    protected $description = 'Clear the response cache';

    public function handle(ResponseCacheRepository $cache)
    {
        event(new FlushingResponseCache());
        event(new ClearingResponseCache());

        $cache->clear();

        event(new FlushedResponseCache());
        event(new ClearedResponseCache());

        $this->info('Response cache cleared!');
    }
}
