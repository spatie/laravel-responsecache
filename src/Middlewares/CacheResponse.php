<?php

namespace Spatie\ResponseCache\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\ResponseCache\ResponseCache;
use Spatie\ResponseCache\Events\CacheMissed;
use Spatie\ResponseCache\Replacers\Replacer;
use Symfony\Component\HttpFoundation\Response;
use Spatie\ResponseCache\Events\ResponseCacheHit;

class CacheResponse
{
    /** @var \Spatie\ResponseCache\ResponseCache */
    protected $responseCache;

    public function __construct(ResponseCache $responseCache)
    {
        $this->responseCache = $responseCache;
    }

    public function handle(Request $request, Closure $next, $lifetimeInSeconds = null): Response
    {
        if ($this->responseCache->enabled($request)) {
            if ($this->responseCache->hasBeenCached($request)) {
                event(new ResponseCacheHit($request));

                $response = $this->responseCache->getCachedResponseFor($request);

                $this->getReplacers()->each(function (Replacer $replacer) use ($response) {
                    $replacer->replaceInCachedResponse($response);
                });

                return $response;
            }
        }

        $response = $next($request);

        if ($this->responseCache->enabled($request)) {
            if ($this->responseCache->shouldCache($request, $response)) {
                $this->makeReplacementsAndCacheResponse($request, $response, $lifetimeInSeconds);
            }
        }

        event(new CacheMissed($request));

        return $response;
    }

    protected function makeReplacementsAndCacheResponse(
        Request $request,
        Response $response,
        $lifetimeInSeconds = null
    ): void {
        $cachedResponse = clone $response;

        $this->getReplacers()->each(function (Replacer $replacer) use ($cachedResponse) {
            $replacer->prepareResponseToCache($cachedResponse);
        });

        $this->responseCache->cacheResponse($request, $cachedResponse, $lifetimeInSeconds);
    }

    protected function getReplacers(): Collection
    {
        return collect(config('responsecache.replacers'))
            ->map(function (string $replacerClass) {
                return app($replacerClass);
            });
    }
}
