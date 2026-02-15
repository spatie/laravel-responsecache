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

class BaseCacheMiddleware
{
    protected function getReplacers(): Collection
    {
        return collect(config('responsecache.replacers'))
            ->map(fn (string $replacerClass) => app($replacerClass));
    }

    public function addCacheAgeHeader(Response $response): Response
    {
        if (config('responsecache.debug.add_age_header')) {
            if (config('responsecache.debug.time_header_name')) {
                $time = $response->headers->get(config('responsecache.debug.time_header_name'));
                $ageInSeconds = (int) Carbon::parse($time)->diffInSeconds(Carbon::now(), true);
                $response->headers->set(config('responsecache.debug.age_header_name'), $ageInSeconds);
            }
        }

        return $response;
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

    protected function addDebugHeaders(Response $response, bool $isHit, string $cacheKey, ?int $age = null): Response
    {
        if (! config('responsecache.debug.add_time_header')) {
            return $response;
        }

        $response->headers->set('X-Response-Cache-Status', $isHit ? 'HIT' : 'MISS');

        if ($isHit && $age !== null) {
            $response->headers->set('X-Response-Cache-Age', (string) $age);
        }

        if (config('app.debug')) {
            $response->headers->set('X-Response-Cache-Key', $cacheKey);
        }

        return $response;
    }
}
