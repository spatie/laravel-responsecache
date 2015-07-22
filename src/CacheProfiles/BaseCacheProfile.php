<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

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

    /**
     * Return the time when the cache must be invalided.
     *
     * @return \DateTime
     */
    public function cacheRequestUntil(Request $request)
    {
        return Carbon::now()->addMinutes($this->app['config']->get('laravel-responsecache.cacheLifetimeInMinutes'));
    }

    /**
     * Set a string to add to differentiate this request from others.
     *
     * @return string
     */
    public function cacheNameSuffix(Request $request)
    {
        if ($this->app->auth->check()) {
            return $this->app->auth->user()->id;
        }

        return '';
    }

    /**
     * Determine if the app is running in the console.
     *
     * To allow testing this will return false the environment is testing.
     *
     * @return bool
     */
    public function isRunningInConsole()
    {
        if ($this->app->environment('testing')) {
            return false;
        }

        return $this->app->runningInConsole();
    }
}
