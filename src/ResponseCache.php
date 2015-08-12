<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @param \Spatie\ResponseCache\ResponseCacheRepository    $cache
     * @param \Spatie\ResponseCache\RequestHasher              $hasher
     * @param \Spatie\ResponseCache\CacheProfiles\CacheProfile $cacheProfile
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
     * @param \Illuminate\Http\Request                   $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return bool
     */
    public function shouldCache(Request $request, Response $response)
    {
        if (!config('laravel-responsecache.enabled')) {
            return false;
        }

        if ($request->attributes->has('laravel-cacheresponse.doNotCache')) {
            return false;
        }

        if (!$this->cacheProfile->shouldCacheRequest($request)) {
            return false;
        }

        return $this->cacheProfile->shouldCacheResponse($response);
    }

    /**
     * Store the given response in the cache.
     *
     * @param \Illuminate\Http\Request                   $request
     * @param \Symfony\Component\HttpFoundation\Response $response
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
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function hasCached(Request $request)
    {
        if (!config('laravel-responsecache.enabled')) {
            return false;
        }

        return $this->cache->has($this->hasher->getHashFor($request));
    }

    /**
     * Get the cached response for the given request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCachedResponseFor(Request $request)
    {
        return $this->cache->get($this->hasher->getHashFor($request));
    }

    /**
     *  Flush the cache.
     */
    public function flush()
    {
        $this->cache->flush();
    }

    /**
     * Add a header with the cache date on the response.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addCachedHeader(Response $response)
    {
        $clonedResponse = clone $response;

        $clonedResponse->headers->set('Laravel-reponsecache', 'cached on '.date('Y-m-d H:i:s'));

        return $clonedResponse;
    }
}
