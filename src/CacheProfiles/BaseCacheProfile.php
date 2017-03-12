<?php

namespace Spatie\ResponseCache\CacheProfiles;

use DateTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Foundation\Application;

abstract class BaseCacheProfile
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /*
     * Return the time when the cache must be invalided.
     */
    public function cacheRequestUntil(Request $request): DateTime
    {
        return Carbon::now()->addMinutes($this->app['config']->get('responsecache.cacheLifetimeInMinutes'));
    }

    /**
     * Set a string to add to differentiate this request from others.
     *
     * @return mixed
     */
    public function cacheNameSuffix(Request $request)
    {
        if ($this->app->auth->check()) {
            return $this->app->auth->user()->id;
        }

        return '';
    }

    public function isRunningInConsole(): bool
    {
        if ($this->app->environment('testing')) {
            return false;
        }

        return $this->app->runningInConsole();
    }
}
