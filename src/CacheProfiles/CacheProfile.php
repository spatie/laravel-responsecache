<?php

namespace Spatie\ResponseCache\CacheProfiles;

use \Illuminate\Http\Request;

interface CacheProfile
{

    /**
     * Determine if the given request should be cached.
     *
     * @return bool
     */
    public function shouldBeCached(Request $request);

    /**
     * Return the time when the cache must be invalided.
     *
     * @return \DateTime
     */
    public function cacheRequestUntil(Request $request);

    /**
     * Set a string to add to differentiate this request from others.
     *
     * @return string
     */
    public function cacheNameSuffix(Request $request);
}
