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
        ?string $driver = null,
    ): string {
        $lifetimeInSeconds = $lifetime instanceof CarbonInterval
            ? (int) $lifetime->totalSeconds
            : $lifetime;

        $config = new CacheConfiguration(
            lifetime: $lifetimeInSeconds,
            tags: is_array($tags) ? $tags : [$tags],
            driver: $driver,
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

        if ($config) {
            $lifetimeInSeconds = $config->lifetime;
            $tags = $config->tags;
        } else {
            $lifetimeInSeconds = $this->getLifetime($args);
            $tags = $this->getTags($args);
        }

        if ($cachedResponse = $this->getCachedResponse($request, $tags)) {
            return $cachedResponse;
        }

        $response = $next($request);

        if ($this->responseCache->shouldCache($request, $response)) {
            $this->cacheResponse($request, $response, $lifetimeInSeconds, $tags);
        }

        event(new CacheMissedEvent($request));

        return $response;
    }

    protected function getCachedResponse(Request $request, array $tags): ?Response
    {
        if (! $this->responseCache->hasBeenCached($request, $tags)) {
            return null;
        }

        try {
            $response = $this->responseCache->getCachedResponseFor($request, $tags);
        } catch (Throwable $exception) {
            report("Could not serve cached response: {$exception->getMessage()}");

            return null;
        }

        event(new ResponseCacheHitEvent($request));

        $response = $this->addCacheAgeHeader($response);

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

    protected function getLifetime(array $args): ?int
    {
        if (isset($args[0]) && is_numeric($args[0])) {
            return (int) $args[0];
        }

        return null;
    }
}
