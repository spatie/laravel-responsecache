<?php

namespace Spatie\ResponseCache\Commands;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

class ClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'responsecache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush the response cache';

    /**
     * The cache manager instance.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /** @var Repository */
    protected $config;

    /**
     * Create a new cache clear command instance.
     *
     * @param \Illuminate\Cache\CacheManager $cache
     * @param Repository                     $config
     */
    public function __construct(CacheManager $cache, Repository $config)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->config = $config;
    }

    public function handle()
    {
        $storeName = $this->config->get('laravel-responsecache.cacheStore');

        $this->laravel['events']->fire('responsecache:clearing', [$storeName]);

        $this->cache->store($storeName)->flush();

        $this->laravel['events']->fire('responsecache:cleared', [$storeName]);

        $this->info('Response cache cleared!');
    }
}
