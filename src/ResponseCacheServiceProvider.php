<?php

namespace Spatie\ResponseCache;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Commands\Flush;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;
use Spatie\ResponseCache\Middlewares\CacheResponse;

class ResponseCacheServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../resources/config/responsecache.php' => config_path('responsecache.php'),
        ], 'config');

        $this->app->bind(CacheProfile::class, function (Application $app) {
            return $app->make(config('responsecache.cacheProfile'));
        });

        $this->app->singleton('responsecache', ResponseCache::class);

        $this->app['command.responsecache:flush'] = $this->app->make(Flush::class);

        $this->commands(['command.responsecache:flush']);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../resources/config/responsecache.php', 'responsecache');
    }
}
