<?php

namespace Spatie\ResponseCache\Commands;

use Illuminate\Console\Command;
use Spatie\ResponseCache\Facades\ResponseCache;
use Spatie\ResponseCache\ResponseCacheRepository;
use Spatie\ResponseCache\Events\ClearedResponseCache;
use Spatie\ResponseCache\Events\ClearingResponseCache;

class ClearCommand extends Command
{
    protected $signature = 'responsecache:clear {--url=}';

    protected $description = 'Clear the response cache';

    public function handle(ResponseCacheRepository $cache)
    {
        event(new ClearingResponseCache());

        if ($url = $this->option('url')) {
            ResponseCache::forget($url);
        } else {
            $cache->clear();
        }

        event(new ClearedResponseCache());

        $this->info('Response cache cleared!');
    }
}
