<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\ResponseCache\CacheItemSelector\CacheItemSelector;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Symfony\Component\HttpFoundation\Response;

class ResponseCache
{
    private CacheProfile $cacheProfile;
    private RequestHasher $hasher;

    public function __construct(
        protected ResponseCacheRepository $cache,
        protected ResponseCacheConfig $cacheConfig,
    ) {
        $this->cacheProfile = $this->cacheConfig->cache_profile;
        $this->hasher = $this->cacheConfig->hasher;
    }

    public function enabled(Request $request): bool
    {
        return $this->cacheProfile->enabled($request, $this->cacheConfig);
    }

    public function shouldCache(Request $request, Response $response): bool
    {
        if ($request->attributes->has('responsecache.doNotCache')) {
            return false;
        }

        if (!$this->cacheProfile->shouldCacheRequest($request, $this->cacheConfig)) {
            return false;
        }

        return $this->cacheProfile->shouldCacheResponse($response, $this->cacheConfig);
    }

    public function cacheResponse(
        Request $request,
        Response $response,
        ?int $lifetimeInSeconds = null,
        array $tags = []
    ): Response {
        if ($this->cacheConfig->add_cache_time_header) {
            $response = $this->addCachedHeader($response);
        }

        $this->taggedCache($tags)->put(
            $this->hasher->getHashFor($request, $this->cacheConfig),
            $response,
            $lifetimeInSeconds ?? $this->cacheProfile->cacheRequestUntil($request, $this->cacheConfig),
        );

        return $response;
    }

    protected function addCachedHeader(Response $response): Response
    {
        $clonedResponse = clone $response;

        $clonedResponse->headers->set(
            $this->cacheConfig->cache_time_header_name,
            Carbon::now()->toRfc2822String(),
        );

        return $clonedResponse;
    }

    protected function taggedCache(array $tags = []): ResponseCacheRepository
    {
        if (empty($tags)) {
            return $this->cache;
        }

        return $this->cache->tags($tags);
    }

    public function hasBeenCached(Request $request, array $tags = []): bool
    {
        return $this->cacheProfile->enabled($request, $this->cacheConfig) &&
            $this->taggedCache($tags)->has($this->hasher->getHashFor($request, $this->cacheConfig));
    }

    public function getCachedResponseFor(Request $request, array $tags = []): Response
    {
        return $this->taggedCache($tags)->get($this->hasher->getHashFor($request, $this->cacheConfig));
    }

    public function clear(array $tags = []): void
    {
        $this->taggedCache($tags)->clear();
    }

    /**
     * @param  string|array  $uris
     * @param  string[]  $tags
     *
     * @return ResponseCache
     */
    public function forget(string|array $uris, array $tags = []): self
    {
        $uris = is_array($uris) ? $uris : func_get_args();
        $this->selectCachedItems()->forUrls($uris)->forget();

        return $this;
    }

    public function selectCachedItems(): CacheItemSelector
    {
        return new CacheItemSelector($this->cacheConfig, $this->cache);
    }

    public function getReplacers(): Collection
    {
        return collect($this->cacheConfig->replacers)
            ->map(fn(string $replacerClass) => app($replacerClass));
    }
}
