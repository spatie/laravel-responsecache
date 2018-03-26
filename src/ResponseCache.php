<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class ResponseCache
{
    /** @var ResponseCache */
    protected $cache;

    /** @var CacheProfile */
    protected $cacheProfile;

    public function __construct(ResponseCacheRepository $cache, CacheProfile $cacheProfile)
    {
        $this->cache = $cache;
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

    public function cacheResponse(Request $request, Response $response, $lifetimeInMinutes = null): Response
    {
        if (config('responsecache.add_cache_time_header')) {
            $response = $this->addCachedHeader($response);
        }

        $this->cache->put(
            $this->cacheProfile->getHashFor($request),
            $response,
            ($lifetimeInMinutes) ? intval($lifetimeInMinutes) : $this->cacheProfile->cacheRequestUntil($request)
        );

        return $response;
    }

    public function hasBeenCached(Request $request): bool
    {
        return config('responsecache.enabled')
            ? $this->cache->has($this->cacheProfile->getHashFor($request))
            : false;
    }

    public function getCachedResponseFor(Request $request): Response
    {
        return $this->cache->get($this->cacheProfile->getHashFor($request));
    }

    /**
     * @deprecated Use the new clear method, this is just an alias.
     */
    public function flush()
    {
        $this->clear();
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
     */
    public function forget($uris): self
    {
        $uris = is_array($uris) ? $uris : func_get_args();

        collect($uris)->each(function ($uri) {
            $request = Request::create($uri);
            $hash = $this->cacheProfile->getHashFor($request);

            if ($this->cache->has($hash)) {
                $this->cache->forget($hash);
            }
        });

        return $this;
    }
}
