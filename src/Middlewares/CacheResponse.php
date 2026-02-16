<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\CarbonInterval;
use Closure;
use Illuminate\Http\Request;
use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Attributes\NoCache;
use Spatie\ResponseCache\Configuration\CacheConfiguration;
use Spatie\ResponseCache\Events\CacheMissedEvent;
use Spatie\ResponseCache\Events\ResponseCacheHitEvent;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\Replacers\Replacer;
use Spatie\ResponseCache\ResponseCache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CacheResponse extends BaseCacheMiddleware
{
    public function __construct(
        protected ResponseCache $responseCache,
    ) {}

    public static function for(
        int|CarbonInterval|null $lifetime = null,
        string|array $tags = [],
    ): string {
        $lifetimeInSeconds = $lifetime instanceof CarbonInterval
            ? (int) $lifetime->totalSeconds
            : $lifetime;

        $config = new CacheConfiguration(
            lifetime: $lifetimeInSeconds,
            tags: is_array($tags) ? $tags : [$tags],
        );

        return static::class.':'.base64_encode(serialize($config));
    }

    public function handle(Request $request, Closure $next, ...$args): Response
    {
        $attribute = $this->getAttributeFromRequest($request);

        if ($attribute instanceof NoCache) {
            return $next($request);
        }

        if ($attribute instanceof FlexibleCache) {
            return app(FlexibleCacheResponse::class)->handle($request, $next);
        }

        if (! $this->responseCache->enabled($request) || $this->responseCache->shouldBypass($request)) {
            return $next($request);
        }

        $config = $attribute instanceof Cache
            ? $attribute
            : $this->getConfigurationFromArgs($args);

        $lifetimeInSeconds = $config?->lifetime;
        $tags = $config?->tags ?? [];

        if ($cachedResponse = $this->getCachedResponse($request, $tags)) {
            return $cachedResponse;
        }

        $response = $next($request);

        if ($this->responseCache->shouldCache($request, $response)) {
            $this->cacheResponse($request, $response, $lifetimeInSeconds, $tags);
        }

        $cacheKey = app(RequestHasher::class)->getHashFor($request);
        $response = $this->addDebugHeaders($response, false, $cacheKey);

        event(new CacheMissedEvent($request));

        return $response;
    }

    protected function getCachedResponse(Request $request, array $tags): ?Response
    {
        if (! $this->responseCache->hasBeenCached($request, $tags)) {
            return null;
        }

        $cacheKey = app(RequestHasher::class)->getHashFor($request);

        try {
            $response = $this->responseCache->getCachedResponseFor($request, $tags);
        } catch (Throwable $exception) {
            report("Could not serve cached response: {$exception->getMessage()}");

            return null;
        }

        $ageInSeconds = $this->getAgeInSeconds($response);

        event(new ResponseCacheHitEvent($request, $ageInSeconds, $tags));

        $response = $this->addDebugHeaders($response, true, $cacheKey, $ageInSeconds);

        $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->replaceInCachedResponse($response));

        return $response;
    }

    protected function cacheResponse(
        Request $request,
        Response $response,
        ?int $lifetimeInSeconds,
        array $tags,
    ): void {
        $cachedResponse = clone $response;

        $this->addCacheTimeHeader($cachedResponse);

        $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->prepareResponseToCache($cachedResponse));

        $this->responseCache->cacheResponse($request, $cachedResponse, $lifetimeInSeconds, $tags);
    }

    protected function getConfigurationFromArgs(array $args): ?CacheConfiguration
    {
        if (! isset($args[0]) || ! is_string($args[0])) {
            return null;
        }

        try {
            $decoded = base64_decode($args[0], true);

            if ($decoded === false) {
                return null;
            }

            $config = unserialize($decoded);

            return $config instanceof CacheConfiguration ? $config : null;
        } catch (Throwable) {
            return null;
        }
    }
}
