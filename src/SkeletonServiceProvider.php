<?php

namespace Spatie\Skeleton;

use Illuminate\Support\ServiceProvider;

class SkeletonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'skeleton');

        $this->publishes([
            __DIR__.'/../resources/config/skeleton.php' => config_path('skeleton.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/skeleton'),
        ], 'views');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../resources/config/config.php', 'skeleton');
    }
}
