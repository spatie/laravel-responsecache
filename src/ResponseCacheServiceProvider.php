<?php

namespace Spatie\ResponseCache;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Commands\ClearCommand;
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

        $this->app['command.responsecache:clear'] = $this->app->make(ClearCommand::class);

        $this->commands(['command.responsecache:clear']);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../resources/config/responsecache.php', 'responsecache');

        $this->app[\Illuminate\Routing\Router::class]->aliasMiddleware('doNotCacheResponse', DoNotCacheResponse::class);
    }
}
