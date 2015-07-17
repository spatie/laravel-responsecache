<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class ResponseCache
{
    /**
     * @var ResponseCacher
     */
    protected $cache;

    /**
     * @var RequestHasher
     */
    protected $hasher;

    /**
     * @var CacheProfile
     */
    protected $cacheProfile;

    /**
     * @param ResponseCacheRepository $cache
     * @param RequestHasher           $hasher
     * @param CacheProfile            $cacheProfile
     */
    public function __construct(ResponseCacheRepository $cache, RequestHasher $hasher, CacheProfile $cacheProfile)
    {
        $this->cache = $cache;
        $this->hasher = $hasher;
        $this->cacheProfile = $cacheProfile;
    }

    /**
     * Determine if the given request should be cached.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function shouldCache(Request $request)
    {
        if ($request->attributes->has('laravel-cacheresponse.doNotCache')) {
            return false;
        }

        return $this->cacheProfile->shouldCache($request);
    }

    /**
     * Store the given response in the cache.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function cacheResponse(Request $request, Response $response)
    {
        if (config('laravel-responsecache.addCacheTimeHeader')) {
            $response = $this->addCachedHeader($response);
        }

        $this->cache->put($this->hasher->getHashFor($request), $response, $this->cacheProfile->cacheRequestUntil($request));
    }

    /**
     * Determine if the given request has been cached.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function hasCached(Request $request)
    {
        return $this->cache->has($this->hasher->getHashFor($request));
    }

    /**
     * Get the cached response for the given request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getCachedResponseFor(Request $request)
    {
        return $this->cache->get($this->hasher->getHashFor($request));
    }

    /**
     * Add a header with the cache date on the response.
     *
     * @param Response $response
     *
     * @return Response
     */
    protected function addCachedHeader(Response $response)
    {
        $clonedResponse = clone $response;

        $clonedResponse->header('Laravel-reponsecache', 'cached on '.date('Y-m-d H:i:s'));

        return $clonedResponse;
    }
}
