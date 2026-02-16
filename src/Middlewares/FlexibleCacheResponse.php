<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\CarbonInterval;
use Closure;
use Illuminate\Http\Request;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Attributes\NoCache;
use Spatie\ResponseCache\Configuration\FlexibleCacheConfiguration;
use Spatie\ResponseCache\Events\CacheMissedEvent;
use Spatie\ResponseCache\Events\ResponseCacheHitEvent;
use Spatie\ResponseCache\Exceptions\SkipCacheException;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\Replacers\Replacer;
use Spatie\ResponseCache\ResponseCache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class FlexibleCacheResponse extends BaseCacheMiddleware
{
    public function __construct(
        protected ResponseCache $responseCache,
    ) {}

    public function handle(Request $request, Closure $next, ...$args): Response
    {
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

        if (! $config) {
            return $next($request);
        }

        return $this->handleFlexibleCache($request, $next, [$config->lifetime, $config->grace], $config->tags);
    }

    public static function for(
        int|CarbonInterval $lifetime,
        int|CarbonInterval $grace,
        string|array $tags = [],
    ): string {
        $lifetimeSeconds = $lifetime instanceof CarbonInterval ? (int) $lifetime->totalSeconds : $lifetime;
        $graceSeconds = $grace instanceof CarbonInterval ? (int) $grace->totalSeconds : $grace;

        $config = new FlexibleCacheConfiguration(
            lifetime: $lifetimeSeconds,
            grace: $graceSeconds,
            tags: is_array($tags) ? $tags : [$tags],
        );

        return static::class.':'.base64_encode(serialize($config));
    }

    protected function handleFlexibleCache(Request $request, Closure $next, array $flexibleTime, array $tags): Response
    {
        $cacheKey = app(RequestHasher::class)->getHashFor($request);
        $wasMiss = false;

        try {
            $response = $this->responseCache->flexible(
                $cacheKey,
                [$flexibleTime[0], $flexibleTime[1]],
                function () use ($request, $next, &$wasMiss) {
                    $wasMiss = true;
                    $response = $next($request);

                    if (! $this->responseCache->shouldCache($request, $response)) {
                        throw new SkipCacheException($response);
                    }

                    $cachedResponse = clone $response;

                    $this->addCacheTimeHeader($cachedResponse);

                    $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->prepareResponseToCache($cachedResponse));

                    return $cachedResponse;
                },
                $tags,
            );
        } catch (SkipCacheException $e) {
            event(new CacheMissedEvent($request));

            return $e->response;
        }

        $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->replaceInCachedResponse($response));

        $ageInSeconds = $this->getAgeInSeconds($response);
        $response = $this->addDebugHeaders($response, ! $wasMiss, $cacheKey, $ageInSeconds);

        if ($wasMiss) {
            event(new CacheMissedEvent($request));

            return $response;
        }

        event(new ResponseCacheHitEvent($request, $ageInSeconds, $tags));

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
        } catch (Throwable) {
            return null;
        }
    }
}
