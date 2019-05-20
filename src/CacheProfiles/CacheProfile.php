<?php

namespace Spatie\ResponseCache\CacheProfiles;

use DateTime;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface CacheProfile
{
    public function enabled(Request $request): bool;

    public function shouldCacheRequest(Request $request): bool;

    public function shouldCacheResponse(Response $response): bool;

    public function cacheRequestUntil(Request $request): DateTime;

    /*
     * Return a string to differentiate this request from others.
     *
     * For example: if you want a different cache per user you could return the id of
     * the logged in user.
     */
    public function useCacheNameSuffix(Request $request): string;
}
