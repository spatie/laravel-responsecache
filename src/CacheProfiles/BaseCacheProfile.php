<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\ResponseCache\ResponseCacheConfig;

abstract class BaseCacheProfile implements CacheProfile
{
    public function enabled(Request $request, ResponseCacheConfig $cacheConfig): bool
    {
        return $cacheConfig->enabled;
    }

    public function cacheRequestUntil(Request $request, ResponseCacheConfig $cacheConfig): DateTime
    {
        return Carbon::now()->addSeconds(
            $cacheConfig->cache_lifetime_in_seconds
        );
    }

    public function useCacheNameSuffix(Request $request, ResponseCacheConfig $cacheConfig): string
    {
        return Auth::check()
            ? (string) Auth::id()
            : '';
    }

    public function isRunningInConsole(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return app()->runningInConsole();
    }
}
