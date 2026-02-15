<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Http\Request;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Attributes\NoCache;
use Spatie\ResponseCache\Configuration\FlexibleCacheConfiguration;
use Spatie\ResponseCache\Events\ResponseCacheHit;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\Replacers\Replacer;
use Spatie\ResponseCache\ResponseCache;
use Symfony\Component\HttpFoundation\Response;

class FlexibleCacheResponse extends BaseCacheMiddleware
{
    protected ResponseCache $responseCache;

    public function __construct(ResponseCache $responseCache)
    {
        $this->responseCache = $responseCache;
    }

    public function handle(Request $request, Closure $next, ...$args): Response
    {
        // Check for attributes first
        $attribute = $this->getAttributeFromRequest($request);

        if ($attribute instanceof NoCache
            || ! $this->responseCache->enabled($request)
            || $this->responseCache->shouldBypass($request)
        ) {
            return $next($request);
        }

        $config = $attribute instanceof FlexibleCache
            ? $attribute
            : $this->getConfigurationFromArgs($args);

        if ($config) {
            $flexibleTime = [$config->fresh, $config->stale];
            $tags = $config->tags;
        } else {
            $flexibleTime = $this->getFlexibleTime($args);
            $tags = $this->getTags($args);
        }

        return $this->handleFlexibleCache($request, $next, $flexibleTime, $tags);
    }

    /**
     * Create a middleware string for flexible/SWR caching.
     *
     * @param  int|CarbonInterval  $fresh  How long the cache is considered fresh
     * @param  int|CarbonInterval  $stale  Stale period (time to serve stale while revalidating)
     * @param  string|array  $tags  Optional cache tags
     */
    public static function for(
        int|CarbonInterval $fresh,
        int|CarbonInterval $stale,
        string|array $tags = [],
    ): string {
        $freshSeconds = $fresh instanceof CarbonInterval ? (int) $fresh->totalSeconds : $fresh;
        $staleSeconds = $stale instanceof CarbonInterval ? (int) $stale->totalSeconds : $stale;

        $config = new FlexibleCacheConfiguration(
            fresh: $freshSeconds,
            stale: $staleSeconds,
            tags: is_array($tags) ? $tags : [$tags],
        );

        return static::class.':'.base64_encode(serialize($config));
    }

    /**
     * @deprecated Use for() instead. Will be removed in v9.0.
     */
    public static function flexible(int|CarbonInterval $freshSeconds, int|CarbonInterval $totalSeconds, ...$tags): string
    {
        $freshSeconds = $freshSeconds instanceof CarbonInterval ? (int) $freshSeconds->totalSeconds : $freshSeconds;
        $totalSeconds = $totalSeconds instanceof CarbonInterval ? (int) $totalSeconds->totalSeconds : $totalSeconds;

        $flexibleTime = "{$freshSeconds}:{$totalSeconds}";

        $middlewareString = static::class.':'.$flexibleTime;

        if (! empty($tags)) {
            $middlewareString .= ','.implode(',', $tags);
        }

        return $middlewareString;
    }

    protected function handleFlexibleCache(Request $request, Closure $next, array $flexibleTime, array $tags): Response
    {
        $cacheKey = app(RequestHasher::class)->getHashFor($request);

        $fresh = $flexibleTime[0];
        $stale = $flexibleTime[1];

        $response = $this->responseCache->flexible(
            $cacheKey,
            [$fresh, $stale],
            function () use ($request, $next) {
                $response = $next($request);

                if (! $this->responseCache->shouldCache($request, $response)) {
                    return $response;
                }

                $cachedResponse = clone $response;

                if (config('responsecache.debug.add_time_header')) {
                    $cachedResponse->headers->set(
                        config('responsecache.debug.time_header_name'),
                        Carbon::now()->toRfc2822String(),
                    );
                }

                $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->prepareResponseToCache($cachedResponse));

                return $cachedResponse;
            },
            $tags,
        );

        $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->replaceInCachedResponse($response));

        $response = $this->addCacheAgeHeader($response);

        event(new ResponseCacheHit($request));

        return $response;
    }

    protected function getConfigurationFromArgs(array $args): ?FlexibleCacheConfiguration
    {
        if (count($args) < 1 || ! is_string($args[0])) {
            return null;
        }

        try {
            $decoded = base64_decode($args[0], true);

            if ($decoded === false) {
                return null;
            }

            $config = unserialize($decoded);

            return $config instanceof FlexibleCacheConfiguration ? $config : null;
        } catch (\Throwable) {
            return null;
        }
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

        if (count($parts) < 2 || count($parts) > 2) {
            return null;
        }

        $fresh = (int) $parts[0];
        $stale = (int) $parts[1];

        if ($fresh <= 0 || $stale <= 0) {
            return null;
        }

        return [$fresh, $stale];
    }
}
