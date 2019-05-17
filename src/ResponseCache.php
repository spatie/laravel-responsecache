<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Hasher\DefaultHasher;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Symfony\Component\HttpFoundation\Response;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class ResponseCache
{
    /** @var \Spatie\ResponseCache\ResponseCache */
    protected $cache;

    /** @var RequestHasher */
    protected $hasher;

    /** @var \Spatie\ResponseCache\CacheProfiles\CacheProfile */
    protected $cacheProfile;

    public function __construct(ResponseCacheRepository $cache, RequestHasher $hasher, CacheProfile $cacheProfile)
    {
        $this->cache = $cache;
        $this->hasher = $hasher;
        $this->cacheProfile = $cacheProfile;
    }

    public function enabled(Request $request): bool
    {
        return $this->cacheProfile->enabled($request);
    }

    public function shouldCache(Request $request, Response $response): bool
    {
        if ($request->attributes->has('responsecache.doNotCache')) {
            return false;
        }

        if (! $this->cacheProfile->shouldCacheRequest($request)) {
            return false;
        }

        return $this->cacheProfile->shouldCacheResponse($response);
    }

    public function cacheResponse(Request $request, Response $response, $lifetimeInSeconds = null): Response
    {
        if (config('responsecache.add_cache_time_header')) {
            $response = $this->addCachedHeader($response);
        }

        $lifetimeInSeconds = $lifetimeInSeconds
            ? (int)$lifetimeInSeconds
            : $this->cacheProfile->cacheRequestUntil($request);

        $this->cache->put(
            $this->hasher->getHashFor($request),
            $response,
            $lifetimeInSeconds,
        );

        return $response;
    }

    public function hasBeenCached(Request $request): bool
    {
        return config('responsecache.enabled')
            ? $this->cache->has($this->hasher->getHashFor($request))
            : false;
    }

    public function getCachedResponseFor(Request $request): Response
    {
        return $this->cache->get($this->hasher->getHashFor($request));
    }

    public function clear()
    {
        $this->cache->clear();
    }

    protected function addCachedHeader(Response $response): Response
    {
        $clonedResponse = clone $response;

        $clonedResponse->headers->set('laravel-responsecache', 'cached on '.date('Y-m-d H:i:s'));

        return $clonedResponse;
    }

    /**
     * @param string|array $uris
     *
     * @return \Spatie\ResponseCache\ResponseCache
     */
    public function forget($uris): self
    {
        $uris = is_array($uris) ? $uris : func_get_args();

        collect($uris)->each(function ($uri) {
            $request = Request::create(url($uri));
            $hash = $this->hasher->getHashFor($request);

            if ($this->cache->has($hash)) {
                $this->cache->forget($hash);
            }
        });

        return $this;
    }
}
