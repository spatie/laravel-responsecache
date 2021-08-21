<?php

namespace Spatie\ResponseCache;

use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Commands\ClearCommand;
use Spatie\ResponseCache\Exceptions\InvalidConfig;
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
        $this->app->singleton(ResponseCacheConfigRepository::class, function () {
            $repository = new ResponseCacheConfigRepository();

            collect(config('responsecache.configs'))
                ->map(fn(array $config) => new ResponseCacheConfig($config))
                ->each(fn(ResponseCacheConfig $cacheConfig) => $repository->addConfig($cacheConfig));

            return $repository;
        });

        $this->app->bind(ResponseCacheConfig::class, function (Container $app) {
            // $configName = ResponseCacheConfigSelector::getConfig();
            $configName = 'default';
            $config = $app->make(ResponseCacheConfigRepository::class)->getConfig($configName);

            if (is_null($config)) {
                throw InvalidConfig::couldNotFindConfig('default');
            }

            return $config;
        });

        $this->app->bind(CacheProfile::class, function (Container $app) {
            return $app->make(ResponseCacheConfig::class)->cache_profile;
        });

        $this->app->bind(RequestHasher::class, function (Container $app) {
            return $app->make(ResponseCacheConfig::class)->hasher;
        });

        $this->app->bind(Serializer::class, function (Container $app) {
            return $app->make(ResponseCacheConfig::class)->serializer;
        });

        $this->app->when(ResponseCacheRepository::class)
            ->needs(Repository::class)
            ->give(function (Container $app): Repository {
                $config = $app->make(ResponseCacheConfig::class);
                $repository = $app->make('cache')->store($config->cache_store);
                if (!empty($config->cache_tag)) {
                    return $repository->tags($config->cache_tag);
                }

                return $repository;
            });

        $this->app->singleton('responsecache', ResponseCache::class);
    }
}
