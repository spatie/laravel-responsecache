<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Symfony\Component\HttpFoundation\Response;

class ResponseCache
{
    protected ResponseCacheRepository $cache;

    protected RequestHasher $hasher;

    protected CacheProfile $cacheProfile;

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

    public function cacheResponse(
        Request $request,
        Response $response,
        ?int $lifetimeInSeconds = null,
        array $tags = []
    ): Response {
        if (config('responsecache.add_cache_time_header')) {
            $response = $this->addCachedHeader($response);
        }

        $this->taggedCache($tags)->put(
            $this->hasher->getHashFor($request),
            $response,
            $lifetimeInSeconds ?? $this->cacheProfile->cacheRequestUntil($request),
        );

        return $response;
    }

    public function hasBeenCached(Request $request, array $tags = []): bool
    {
        return config('responsecache.enabled')
            ? $this->taggedCache($tags)->has($this->hasher->getHashFor($request))
            : false;
    }

    public function getCachedResponseFor(Request $request, array $tags = []): Response
    {
        return $this->taggedCache($tags)->get($this->hasher->getHashFor($request));
    }

    public function clear(array $tags = [])
    {
        $this->taggedCache($tags)->clear();
    }

    protected function addCachedHeader(Response $response): Response
    {
        $clonedResponse = clone $response;

        $clonedResponse->headers->set(
            config('responsecache.cache_time_header_name'),
            Carbon::now()->toRfc2822String(),
        );

        return $clonedResponse;
    }

    /**
     * @param string|array $uris
     * @param string[]        $tags
     *
     * @return \Spatie\ResponseCache\ResponseCache
     */
    public function forget($uris, array $tags = []): self
    {
        $uris = is_array($uris) ? $uris : func_get_args();

        collect($uris)->each(function ($uri) use ($tags) {
            $request = Request::create(url($uri));
            $hash = $this->hasher->getHashFor($request);

            if ($this->taggedCache($tags)->has($hash)) {
                $this->taggedCache($tags)->forget($hash);
            }
        });

        return $this;
    }

    protected function taggedCache(array $tags = []): ResponseCacheRepository
    {
        if (empty($tags)) {
            return $this->cache;
        }

        return $this->cache->tags($tags);
    }
}
