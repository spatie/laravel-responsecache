<?php

namespace Spatie\ResponseCache;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponseMiddleware;
use Spatie\ResponseCache\Middlewares\ResponseCacheMiddleware;

class LumenResponseCacheServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->app->bind(CacheProfile::class, function (Container $app) {
            return $app->make(config('laravel-responsecache.cacheProfile'));
        });

        $this->app->singleton('laravel-responsecache', ResponseCache::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../resources/config/laravel-responsecache.php', 'laravel-responsecache');

        $this->app->middleware([ResponseCacheMiddleware::class]);
        $this->app->routeMiddleware([
            'doNotCacheResponse' => DoNotCacheResponseMiddleware::class,
        ]);
    }
}
