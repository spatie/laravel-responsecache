<?php

namespace Spatie\ResponseCache;

use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Commands\ClearCommand;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\Serializers\Serializer;

class ResponseCacheServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-responsecache')
            ->hasConfigFile()
            ->hasCommands([
                ClearCommand::class,
            ]);
    }

    public function packageBooted()
    {
        $this->app->bind(CacheProfile::class, function (Container $app) {
            return $app->make(config('responsecache.cache_profile'));
        });

        $this->app->bind(RequestHasher::class, function (Container $app) {
            return $app->make(config('responsecache.hasher'));
        });

        $this->app->bind(Serializer::class, function (Container $app) {
            return $app->make(config('responsecache.serializer'));
        });

        $this->app->when(ResponseCacheRepository::class)
            ->needs(Repository::class)
            ->give(function (): Repository {
                $repository = app('cache')->store(config('responsecache.cache_store'));
                if (! empty(config('responsecache.cache_tag'))) {
                    return $repository->tags(config('responsecache.cache_tag'));
                }

                return $repository;
            });

        $this->app->singleton('responsecache', ResponseCache::class);
    }
}
