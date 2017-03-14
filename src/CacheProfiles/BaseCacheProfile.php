<?php

namespace Spatie\ResponseCache\CacheProfiles;

use DateTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Foundation\Application;

abstract class BaseCacheProfile
{

    public function enabled(Request $request): bool
    {
        return config('responsecache.enabled');
    }


    /*
     * Return the time when the cache must be invalided.
     */
    public function cacheRequestUntil(Request $request): DateTime
    {
        return Carbon::now()->addMinutes(config('responsecache.cacheLifetimeInMinutes'));
    }

    /**
     * Set a string to add to differentiate this request from others.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function cacheNameSuffix(Request $request)
    {
        if (auth()->check()) {
            return auth()->user()->id;
        }

        return '';
    }

    public function isRunningInConsole(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return app()->runningInConsole();
    }
}
