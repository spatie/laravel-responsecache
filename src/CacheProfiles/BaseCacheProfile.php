<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class BaseCacheProfile implements CacheProfile
{
    public function enabled(Request $request): bool
    {
        return config('responsecache.enabled');
    }

    public function cacheRequestUntil(Request $request): DateTime
    {
        return Carbon::now()->addSeconds(
            config('responsecache.cache_lifetime_in_seconds')
        );
    }

    public function useCacheNameSuffix(Request $request): string
    {
        return Auth::check()
            ? (string) Auth::id()
            : '';
    }

    public function flexibleCacheTime(Request $request): ?array
    {
        if (! config('responsecache.flexible_cache_enabled')) {
            return null;
        }

        return config('responsecache.flexible_cache_time');
    }

    public function isRunningInConsole(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return app()->runningInConsole();
    }
}
