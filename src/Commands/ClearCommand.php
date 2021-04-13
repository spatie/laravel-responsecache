<?php

namespace Spatie\ResponseCache\Commands;

use Illuminate\Console\Command;
use Spatie\ResponseCache\Events\ClearedResponseCache;
use Spatie\ResponseCache\Events\ClearingResponseCache;
use Spatie\ResponseCache\ResponseCacheRepository;

class ClearCommand extends Command
{
    protected $signature = 'responsecache:clear {--url=}';

    protected $description = 'Clear the response cache';

    public function handle(ResponseCacheRepository $cache)
    {
        event(new ClearingResponseCache());

        if ($url = $this->option('url')) {
            $cache->forget($url);
        } else {
            $this->clear($cache);
        }

        event(new ClearedResponseCache());

        $this->info('Response cache cleared!');
    }
}
