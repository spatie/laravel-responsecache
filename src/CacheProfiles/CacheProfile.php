<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Illuminate\Http\Request;

interface CacheProfile
{
    /**
     * Determine if the given request should be cached.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function shouldCache(Request $request);

    /**
     * Return the time when the cache must be invalided.
     *
     * @param \Illuminate\Http\Request  $request
     *
     * @return \DateTime
     */
    public function cacheRequestUntil(Request $request);

    /**
     * Return a string to differentiate this request from others.
     *
     * For example: if you want a different cache per user you could return the id of
     * the logged in user.
     *
     * @param \Illuminate\Http\Request  $request
     *
     * @return string
     */
    public function cacheNameSuffix(Request $request);
}
