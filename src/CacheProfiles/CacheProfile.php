<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Illuminate\Http\Request;

interface CacheProfile
{
    /**
     * Determine if the given request should be cached.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function shouldCache(Request $request);

    /**
     * Return the time when the cache must be invalided.
     *
     * @param Request $request
     *
     * @return \DateTime
     */
    public function cacheRequestUntil(Request $request);

    /**
     * Set a string to add to differentiate this request from others.
     *
     * @param Request $request
     *
     * @return string
     */
    public function cacheNameSuffix(Request $request);
}
