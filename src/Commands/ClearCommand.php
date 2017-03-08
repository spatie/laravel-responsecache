<?php

namespace Spatie\ResponseCache\Commands;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;

class ClearCommand extends Command
{
    protected $signature = 'responsecache:clear';

    protected $description = 'Flush the response cache';

    /**
     * The cache manager instance.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    public function __construct(CacheManager $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    public function handle()
    {
        $storeName = config('responsecache.cacheStore');

        $this->laravel['events']->fire('responsecache:clearing', [$storeName]);

        $this->cache->store($storeName)->flush();

        $this->laravel['events']->fire('responsecache:cleared', [$storeName]);

        $this->info('Response cache cleared!');
    }
}
