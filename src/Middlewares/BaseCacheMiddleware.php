<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Attributes\NoCache;
use Spatie\ResponseCache\Support\AttributeReader;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseCacheMiddleware
{
    protected function getReplacers(): Collection
    {
        return collect(config('responsecache.replacers'))
            ->map(fn (string $replacerClass) => app($replacerClass));
    }

    protected function getAttributeFromRequest(Request $request): Cache|FlexibleCache|NoCache|null
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        $action = $route->getAction('controller');
        if (! $action) {
            return null;
        }

        return AttributeReader::getFirstAttribute(
            $action,
            [Cache::class, FlexibleCache::class, NoCache::class]
        );
    }

    protected function addDebugHeaders(Response $response, bool $isHit, string $cacheKey, ?int $ageInSeconds = null): Response
    {
        if (! config('responsecache.debug.enabled')) {
            return $response;
        }

        $response->headers->set(config('responsecache.debug.cache_status_header_name'), $isHit ? 'HIT' : 'MISS');

        if ($isHit && $ageInSeconds !== null) {
            $response->headers->set(config('responsecache.debug.cache_age_header_name'), (string) $ageInSeconds);
        }

        if (config('app.debug')) {
            $response->headers->set(config('responsecache.debug.cache_key_header_name'), $cacheKey);
        }

        return $response;
    }

    protected function addCacheTimeHeader(Response $response): void
    {
        if (config('responsecache.debug.enabled')) {
            $response->headers->set(
                config('responsecache.debug.cache_time_header_name'),
                Carbon::now()->toRfc2822String(),
            );
        }
    }

    protected function getAgeInSeconds(Response $response): ?int
    {
        $time = $response->headers->get(config('responsecache.debug.cache_time_header_name'));

        if (! $time) {
            return null;
        }

        return (int) Carbon::parse($time)->diffInSeconds(Carbon::now(), true);
    }
}
