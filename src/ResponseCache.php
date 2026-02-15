<?php

namespace Spatie\ResponseCache;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\ResponseCache\CacheItemSelector\CacheItemSelector;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Concerns\TaggedCacheAware;
use Spatie\ResponseCache\Events\ClearedResponseCacheEvent;
use Spatie\ResponseCache\Events\ClearingResponseCacheEvent;
use Spatie\ResponseCache\Events\ClearingResponseCacheFailedEvent;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Symfony\Component\HttpFoundation\Response;

class ResponseCache
{
    use TaggedCacheAware;

    public function __construct(
        protected ResponseCacheRepository $cache,
        protected RequestHasher $hasher,
        protected CacheProfile $cacheProfile,
    ) {
        //
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

    public function shouldBypass(Request $request): bool
    {
        // Ensure we return if cache_bypass_header is not setup
        if (! config('responsecache.bypass.header_name')) {
            return false;
        }
        // Ensure we return if cache_bypass_header is not setup
        if (! config('responsecache.bypass.header_value')) {
            return false;
        }

        return $request->header(config('responsecache.bypass.header_name')) === (string) config('responsecache.bypass.header_value');
    }

    public function cacheResponse(
        Request $request,
        Response $response,
        ?int $lifetimeInSeconds = null,
        array $tags = []
    ): Response {
        if (config('responsecache.debug.add_time_header')) {
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

    public function clear(array $tags = []): bool
    {
        event(new ClearingResponseCacheEvent);

        $result = $this->taggedCache($tags)->clear();

        $resultEvent = $result
            ? new ClearedResponseCacheEvent
            : new ClearingResponseCacheFailedEvent;

        event($resultEvent);

        return $result;
    }

    protected function addCachedHeader(Response $response): Response
    {
        $clonedResponse = clone $response;

        $clonedResponse->headers->set(
            config('responsecache.debug.time_header_name'),
            Carbon::now()->toRfc2822String(),
        );

        return $clonedResponse;
    }

    /**
     * @param  string[]  $tags
     */
    public function forget(string|array $uris, array $tags = []): self
    {
        event(new ClearingResponseCacheEvent);

        $uris = is_array($uris) ? $uris : func_get_args();
        $this->selectCachedItems()->forUrls($uris)->forget();

        event(new ClearedResponseCacheEvent);

        return $this;
    }

    public function selectCachedItems(): CacheItemSelector
    {
        return new CacheItemSelector($this->hasher, $this->cache);
    }

    /**
     * Get a cached response using flexible/SWR (stale-while-revalidate) strategy.
     *
     * @param  array{0: int, 1: int}  $seconds  [fresh_seconds, total_seconds]
     * @param  Closure  $callback  Callback that returns a Response object
     */
    public function flexible(string $key, array $seconds, Closure $callback, array $tags = []): Response
    {
        return $this->taggedCache($tags)->flexible($key, $seconds, $callback);
    }
}
