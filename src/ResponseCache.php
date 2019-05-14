<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Replacers\Replacer;
use Symfony\Component\HttpFoundation\Response;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class ResponseCache
{
    /** @var \Spatie\ResponseCache\ResponseCacheRepository */
    protected $cache;

    /** @var \Spatie\ResponseCache\RequestHasher */
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

        $this->cache->put(
            $this->hasher->getHashFor($request),
            $response,
            ($lifetimeInSeconds) ? intval($lifetimeInSeconds) : $this->cacheProfile->cacheRequestUntil($request)
        );

        foreach (config('responsecache.replacers', []) as $replacerClass) {
            $replacer = resolve($replacerClass);
            if ($replacer instanceof Replacer) {
                $this->cache->putKey(
                    $this->hasher->getHashFor($request).$replacer->searchFor(),
                    $replacer->replaceBy(),
                    ($lifetimeInSeconds) ? intval($lifetimeInSeconds) : $this->cacheProfile->cacheRequestUntil($request)
                );
            }
        }

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

    public function getCachedKeyFor(Request $request, string $key): string
    {
        return $this->cache->getKey($this->hasher->getHashFor($request).$key);
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
     *
     * @return \Spatie\ResponseCache\ResponseCache
     */
    public function forget($uris): self
    {
        $uris = is_array($uris) ? $uris : func_get_args();

        collect($uris)->each(function ($uri) {
            $request = Request::create($uri);
            $hash = $this->hasher->getHashFor($request);

            if ($this->cache->has($hash)) {
                $this->cache->forget($hash);
            }
        });

        return $this;
    }
}
