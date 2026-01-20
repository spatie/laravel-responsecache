<?php

namespace Spatie\ResponseCache\Middlewares;

use Closure;
use Illuminate\Http\Request;
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

    public static function using($lifetime, ...$tags): string
    {
        return static::class.':'.implode(',', [$lifetime, ...$tags]);
    }

    public function handle(Request $request, Closure $next, ...$args): Response
    {
        $lifetimeInSeconds = $this->getLifetime($args);
        $tags = $this->getTags($args);

        if ($this->shouldSkipGlobalMiddleware($request, $lifetimeInSeconds)) {
            return $next($request);
        }

        if ($this->responseCache->enabled($request)) {
            if (! $this->responseCache->shouldBypass($request)) {
                try {
                    if ($this->responseCache->hasBeenCached($request, $tags)) {
                        $response = $this->getCachedResponse($request, $tags);
                        if ($response !== false) {
                            return $response;
                        }
                    }
                } catch (CouldNotUnserialize $e) {
                    report("Could not unserialize response, returning uncached response instead. Error: {$e->getMessage()}");
                    event(new CacheMissed($request));
                }
            }
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

    protected function getLifetime(array $args): ?int
    {
        if (count($args) >= 1 && is_numeric($args[0])) {
            return (int) $args[0];
        }

        return null;
    }

    protected function shouldSkipGlobalMiddleware(Request $request, ?int $lifetimeInSeconds): bool
    {
        // If this middleware has explicit args, don't skip (it's route-specific)
        if ($lifetimeInSeconds !== null) {
            return false;
        }

        $route = $request->route();
        if (! $route) {
            return false;
        }

        $middlewares = $route->gatherMiddleware();

        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                if (str_starts_with($middleware, static::class.':')) {
                    return true;
                }
            }

            if (is_string($middleware)) {
                if (str_starts_with($middleware, FlexibleCacheResponse::class.':')) {
                    return true;
                }
            }
        }

        return false;
    }

}
