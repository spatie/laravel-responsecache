<?php

namespace Spatie\ResponseCache;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Commands\ClearCommand;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponseMiddleware;
use Spatie\ResponseCache\Middlewares\ResponseCacheMiddleware;

class ResponseCacheServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../resources/config/laravel-responsecache.php' => config_path('laravel-responsecache.php'),
        ], 'config');

        $this->app->bind(CacheProfile::class, function (Application $app) {
            return $app->make(config('laravel-responsecache.cacheProfile'));
        });

        $this->app->singleton('laravel-responsecache', ResponseCache::class);

        $this->app['command.responsecache:clear'] = $this->app->make(ClearCommand::class);

        $this->commands(['command.responsecache:clear']);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../resources/config/laravel-responsecache.php', 'laravel-responsecache');

        $this->app[\Illuminate\Contracts\Http\Kernel::class]->pushMiddleware(ResponseCacheMiddleware::class);
        $this->app[\Illuminate\Routing\Router::class]->middleware('doNotCacheResponse', DoNotCacheResponseMiddleware::class);
    }
}
