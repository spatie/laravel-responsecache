<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\ResponseCache\Events\CacheMissed;
use Spatie\ResponseCache\Events\ResponseCacheHit;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
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

    public function handle(Request $request, Closure $next, ...$args): Response
    {
        $lifetimeInSeconds = $this->getLifetime($args);
        $tags = $this->getTags($args);

        if ($this->responseCache->enabled($request) && ! $this->responseCache->shouldBypass($request)) {
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

        if (count($args) >= 1 && is_numeric($args[0])) {
            $tags = array_slice($args, 1);
        }

        return array_filter($tags);
    }

    public function addCacheAgeHeader(Response $response): Response
    {
        if (config('responsecache.add_cache_age_header') and $time = $response->headers->get(config('responsecache.cache_time_header_name'))) {
            $ageInSeconds = Carbon::parse($time)->diffInSeconds(Carbon::now());

            $response->headers->set(config('responsecache.cache_age_header_name'), $ageInSeconds);
        }

        return $response;
    }
}
