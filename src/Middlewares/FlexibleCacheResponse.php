<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Http\Request;
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
        $flexibleTime = $this->getFlexibleTime($args);
        $tags = $this->getTags($args);

        if (! $this->responseCache->enabled($request) || $this->responseCache->shouldBypass($request)) {
            return $next($request);
        }

        return $this->handleFlexibleCache($request, $next, $flexibleTime, $tags);
    }

    /**
     * Create a middleware string for flexible/SWR caching.
     *
     * @param int|CarbonInterval $freshSeconds How long the cache is considered fresh
     * @param int|CarbonInterval $totalSeconds Total cache lifetime (fresh + stale period)
     * @param bool $defer Whether to always defer refresh to background (default: false)
     * @param string ...$tags Optional cache tags
     */
    public static function flexible(int|CarbonInterval $freshSeconds, int|CarbonInterval $totalSeconds, bool $defer = false, ...$tags): string
    {
        $freshSeconds = $freshSeconds instanceof CarbonInterval ? (int) $freshSeconds->totalSeconds : $freshSeconds;
        $totalSeconds = $totalSeconds instanceof CarbonInterval ? (int) $totalSeconds->totalSeconds : $totalSeconds;

        $deferFlag = $defer ? '1' : '0';
        $flexibleTime = "{$freshSeconds}:{$totalSeconds}:{$deferFlag}";

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
        $defer = $flexibleTime[2] ?? false;

        $response = $this->responseCache->flexible(
            $cacheKey,
            [$fresh, $stale],
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
            $defer
        );

        $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->replaceInCachedResponse($response));

        $response = $this->addCacheAgeHeader($response);

        event(new ResponseCacheHit($request));

        return $response;
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

        if (count($parts) < 2 || count($parts) > 3) {
            return null;
        }

        $fresh = (int) $parts[0];
        $stale = (int) $parts[1];
        $defer = isset($parts[2]) ? $parts[2] === '1' : false;

        if ($fresh <= 0 || $stale <= 0) {
            return null;
        }

        return [$fresh, $stale, $defer];
    }


}
