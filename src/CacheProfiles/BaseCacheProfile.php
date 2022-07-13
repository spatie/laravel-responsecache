<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseCacheProfile implements CacheProfile
{
    public function enabled(Request $request): bool
    {
        return config('responsecache.enabled');
    }

    public function cacheRequestUntil(Request $request, Response $response): DateTime
    {
        $ttl = config('responsecache.cache_lifetime_in_seconds');

        if ($expiry = $response->getExpires()) {
            $ttl = Carbon::now()->diffInSeconds($expiry);
        }

        return Carbon::now()->addSeconds($ttl);
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
}
