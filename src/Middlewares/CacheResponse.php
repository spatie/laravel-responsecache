<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\CarbonInterval;
use Closure;
use Illuminate\Http\Request;
use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Attributes\NoCache;
use Spatie\ResponseCache\Configuration\CacheConfiguration;
use Spatie\ResponseCache\Events\CacheMissed;
use Spatie\ResponseCache\Events\ResponseCacheHit;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\Replacers\Replacer;
use Spatie\ResponseCache\ResponseCache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CacheResponse extends BaseCacheMiddleware
{
    protected ResponseCache $responseCache;

    public function __construct(ResponseCache $responseCache)
    {
        $this->responseCache = $responseCache;
    }

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

        return static::class . ':' . base64_encode(serialize($config));
    }

    /**
     * @deprecated Use for() instead. Will be removed in v9.0.
     */
    public static function using($lifetime, ...$tags): string
    {
        return static::class.':'.implode(',', [$lifetime, ...$tags]);
    }

    public function handle(Request $request, Closure $next, ...$args): Response
    {
        // Check for attributes first
        $attribute = $this->getAttributeFromRequest($request);

        if ($attribute instanceof NoCache) {
            return $next($request);
        }

        if ($attribute instanceof FlexibleCache) {
            return app(FlexibleCacheResponse::class)->handle($request, $next);
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

        try {
            if ($this->responseCache->enabled($request)
                && ! $this->responseCache->shouldBypass($request)
                && $this->responseCache->hasBeenCached($request, $tags)
            ) {
                $response = $this->getCachedResponse($request, $tags);

                if ($response !== false) {
                    return $response;
                }
            }
        } catch (\Throwable $e) {
            report("Could not unserialize response, returning uncached response instead. Error: {$e->getMessage()}");
            event(new CacheMissed($request));
        }

        $response = $next($request);

        if ($this->responseCache->enabled($request) && ! $this->responseCache->shouldBypass($request)) {
            if ($this->responseCache->shouldCache($request, $response)) {
                $this->makeReplacementsAndCacheResponse($request, $response, $lifetimeInSeconds, $tags);
            }
        }

        event(new CacheMissed($request));

        return $response;
    }

    protected function getCachedResponse(Request $request, array $tags = []): false|Response
    {
        try {
            $response = $this->responseCache->getCachedResponseFor($request, $tags);
        } catch (CouldNotUnserialize $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report("Unable to retrieve cached response when one was expected. Error: {$exception->getMessage()}");

            return false;
        }

        event(new ResponseCacheHit($request));

        $response = $this->addCacheAgeHeader($response);

        $this->getReplacers()->each(function (Replacer $replacer) use ($response) {
            $replacer->replaceInCachedResponse($response);
        });

        return $response;
    }

    protected function makeReplacementsAndCacheResponse(
        Request $request,
        Response $response,
        ?int $lifetimeInSeconds = null,
        array $tags = []
    ): void {
        $cachedResponse = clone $response;

        $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->prepareResponseToCache($cachedResponse));

        $this->responseCache->cacheResponse($request, $cachedResponse, $lifetimeInSeconds, $tags);
    }

    protected function getConfigurationFromArgs(array $args): ?CacheConfiguration
    {
        if (count($args) >= 1 && is_string($args[0])) {
            try {
                $decoded = base64_decode($args[0], true);
                if ($decoded !== false) {
                    $config = unserialize($decoded);
                    if ($config instanceof CacheConfiguration) {
                        return $config;
                    }
                }
            } catch (\Throwable) {
                // Not a configuration object, fall through to legacy parsing
            }
        }

        return null;
    }

    protected function getLifetime(array $args): ?int
    {
        if (count($args) >= 1 && is_numeric($args[0])) {
            return (int) $args[0];
        }

        return null;
    }

    protected function shouldSkipGlobalMiddleware(Request $request, ?int $lifetimeInSeconds, ?object $attribute = null): bool
    {
        // If this middleware has explicit args or attributes, don't skip (it's route-specific)
        if ($lifetimeInSeconds !== null || $attribute !== null) {
            return false;
        }

        $route = $request->route();
        if (! $route) {
            return false;
        }

        $middlewares = $route->gatherMiddleware();

        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                if (str_starts_with($middleware, static::class.':') ||
                    str_starts_with($middleware, FlexibleCacheResponse::class.':')) {
                    return true;
                }
            }
        }

        return false;
    }

}
