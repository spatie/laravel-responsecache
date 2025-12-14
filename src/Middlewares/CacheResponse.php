<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\ResponseCache\Events\CacheMissed;
use Spatie\ResponseCache\Events\ResponseCacheHit;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\Replacers\Replacer;
use Spatie\ResponseCache\ResponseCache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CacheResponse
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

    /**
     * Create a middleware string for flexible/SWR caching.
     *
     * @param int $freshSeconds How long the cache is considered fresh
     * @param int $totalSeconds Total cache lifetime (fresh + stale period)
     * @param string ...$tags Optional cache tags
     * @return string
     */
    public static function flexible(int $freshSeconds, int $totalSeconds, ...$tags): string
    {
        $flexibleTime = "{$freshSeconds}:{$totalSeconds}";

        if (empty($tags)) {
            return static::class.':'.$flexibleTime;
        }

        return static::class.':'.implode(',', [$flexibleTime, ...$tags]);
    }

    public function handle(Request $request, Closure $next, ...$args): Response
    {
        $lifetimeInSeconds = $this->getLifetime($args);
        $flexibleTime = $this->getFlexibleTime($args);
        $tags = $this->getTags($args);

        if (! $this->responseCache->enabled($request) || $this->responseCache->shouldBypass($request)) {
            return $next($request);
        }

        // Skip global middleware if route has explicit cache middleware
        if ($this->shouldSkipGlobalMiddleware($request, $flexibleTime, $lifetimeInSeconds)) {
            return $next($request);
        }

        if ($this->shouldUseFlexibleCache($request, $flexibleTime)) {
            return $this->handleFlexibleCache($request, $next, $flexibleTime, $tags);
        }

        return $this->handleTraditionalCache($request, $next, $lifetimeInSeconds, $tags);
    }

    protected function shouldSkipGlobalMiddleware(Request $request, ?array $flexibleTime, ?int $lifetimeInSeconds): bool
    {
        // If this middleware has explicit args, don't skip (it's route-specific)
        if ($flexibleTime !== null || $lifetimeInSeconds !== null) {
            return false;
        }

        // Check if route has explicit CacheResponse middleware
        $route = $request->route();
        if (! $route) {
            return false;
        }

        $middlewares = $route->gatherMiddleware();
        foreach ($middlewares as $middleware) {
            // Check if it's a CacheResponse middleware with parameters
            if (is_string($middleware) && str_starts_with($middleware, static::class.':')) {
                return true; // Skip global middleware, let route handle it
            }
        }

        return false;
    }

    protected function handleTraditionalCache(Request $request, Closure $next, ?int $lifetimeInSeconds, array $tags): Response
    {
        try {
            if ($this->responseCache->hasBeenCached($request, $tags)) {
                $response = $this->getCachedResponse($request, $tags);
                if ($response !== false) {
                    if (config('responsecache.add_cache_freshness_header')) {
                        $response->headers->set(
                            config('responsecache.cache_freshness_header_name'),
                            'fresh'
                        );
                    }

                    return $response;
                }
            }
        } catch (CouldNotUnserialize $e) {
            report("Could not unserialize response, returning uncached response instead. Error: {$e->getMessage()}");
            event(new CacheMissed($request));
        }

        $response = $next($request);

        if ($this->responseCache->shouldCache($request, $response)) {
            $this->makeReplacementsAndCacheResponse($request, $response, $lifetimeInSeconds, $tags);
        }

        event(new CacheMissed($request));

        return $response;
    }

    protected function handleFlexibleCache(Request $request, Closure $next, array $flexibleTime, array $tags): Response
    {
        $cacheKey = app(RequestHasher::class)->getHashFor($request);

        $response = $this->responseCache->flexible(
            $cacheKey,
            $flexibleTime,
            function () use ($request, $next) {
                $response = $next($request);

                if (! $this->responseCache->shouldCache($request, $response)) {
                    return $response;
                }

                $cachedResponse = clone $response;

                if (config('responsecache.add_cache_time_header')) {
                    $cachedResponse->headers->set(
                        config('responsecache.cache_time_header_name'),
                        Carbon::now()->toRfc2822String(),
                    );
                }

                $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->prepareResponseToCache($cachedResponse));

                return $cachedResponse;
            },
            $tags,
            config('responsecache.flexible_always_defer', false)
        );

        $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->replaceInCachedResponse($response));

        if (config('responsecache.add_cache_freshness_header')) {
            $response->headers->set(
                config('responsecache.cache_freshness_header_name'),
                'flexible'
            );
        }

        $response = $this->addCacheAgeHeader($response);

        event(new ResponseCacheHit($request));

        return $response;
    }

    protected function shouldUseFlexibleCache(Request $request, ?array $flexibleTime): bool
    {
        // Only use flexible cache when explicitly set via CacheResponse::flexible()
        return $flexibleTime !== null;
    }

    protected function getFlexibleTime(array $args): ?array
    {
        if (count($args) < 1) {
            return null;
        }

        if (! is_string($args[0])) {
            return null;
        }

        if (! str_contains($args[0], ':')) {
            return null;
        }

        $parts = explode(':', $args[0]);

        if (count($parts) !== 2) {
            return null;
        }

        $fresh = (int) $parts[0];
        $stale = (int) $parts[1];

        if ($fresh <= 0 || $stale <= 0) {
            return null;
        }

        return [$fresh, $stale];
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

    protected function getReplacers(): Collection
    {
        return collect(config('responsecache.replacers'))
            ->map(fn (string $replacerClass) => app($replacerClass));
    }

    protected function getLifetime(array $args): ?int
    {
        if (count($args) >= 1 && is_numeric($args[0])) {
            return (int) $args[0];
        }

        return null;
    }

    protected function getTags(array $args): array
    {
        $tags = $args;

        if (count($args) >= 1) {
            if (is_numeric($args[0]) || str_contains($args[0], ':')) {
                $tags = array_slice($args, 1);
            }
        }

        return array_filter($tags);
    }

    public function addCacheAgeHeader(Response $response): Response
    {
        if (config('responsecache.add_cache_age_header') and $time = $response->headers->get(config('responsecache.cache_time_header_name'))) {
            $ageInSeconds = (int) Carbon::parse($time)->diffInSeconds(Carbon::now(), true);

            $response->headers->set(config('responsecache.cache_age_header_name'), $ageInSeconds);
        }

        return $response;
    }
}
