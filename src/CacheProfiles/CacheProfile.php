<?php

namespace Spatie\ResponseCache\CacheProfiles;

use DateTime;
use Illuminate\Http\Request;
use Spatie\ResponseCache\ResponseCacheConfig;
use Symfony\Component\HttpFoundation\Response;

interface CacheProfile
{
    public function enabled(Request $request, ResponseCacheConfig $cacheConfig): bool;

    public function shouldCacheRequest(Request $request, ResponseCacheConfig $cacheConfig): bool;

    public function shouldCacheResponse(Response $response, ResponseCacheConfig $cacheConfig): bool;

    public function cacheRequestUntil(Request $request, ResponseCacheConfig $cacheConfig): DateTime;

    /*
     * Return a string to differentiate this request from others.
     *
     * For example: if you want a different cache per user you could return the id of
     * the logged-in user.
     */
    public function useCacheNameSuffix(Request $request, ResponseCacheConfig $cacheConfig): string;
}
