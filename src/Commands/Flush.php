<?php

namespace Spatie\ResponseCache\Commands;

use Illuminate\Console\Command;
use Spatie\ResponseCache\ResponseCacheRepository;
use Spatie\ResponseCache\Events\FlushedResponseCache;
use Spatie\ResponseCache\Events\FlushingResponseCache;

class Flush extends Command
{
    protected $signature = 'responsecache:flush';

    protected $description = 'Flush the response cache';

    public function handle(ResponseCacheRepository $cache)
    {
        event(new FlushingResponseCache());

        $cache->flush();

        event(new FlushedResponseCache());

        $this->info('Response cache flushed!');
    }
}
