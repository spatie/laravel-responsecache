<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class BaseCacheProfile implements CacheProfile
{
    public function enabled(Request $request): bool
    {
        return config('responsecache.enabled');
    }

    public function cacheLifetimeInSeconds(Request $request): int
    {
        return config('responsecache.cache.lifetime_in_seconds');
    }

    public function useCacheNameSuffix(Request $request): string
    {
        return Auth::check() ? (string) Auth::id() : '';
    }

    protected function isRunningInConsole(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return app()->runningInConsole();
    }
}
