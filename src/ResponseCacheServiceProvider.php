<?php

namespace Spatie\ResponseCache;

use Illuminate\Support\ServiceProvider;

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

    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../resources/config/laravel-responsecache.php', 'laravel-responsecache.php');
    }
}
