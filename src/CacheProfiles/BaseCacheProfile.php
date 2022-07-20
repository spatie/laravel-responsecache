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

    public function isRunningInConsole(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return app()->runningInConsole();
    }

    public function requestHasCacheBypassHeader(Request $request): bool
    {
        // Ensure we return if cache_bypass_header is not setup
        if (! config('responsecache.cache_bypass_header.name')) {
            return false;
        }
        // Ensure we return if cache_bypass_header is not setup
        if (! config('responsecache.cache_bypass_header.value')) {
            return false;
        }

        return $request->header(config('responsecache.cache_bypass_header.name')) === config('responsecache.cache_bypass_header.value');
    }
}
