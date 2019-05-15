<?php

namespace Spatie\ResponseCache\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Spatie\ResponseCache\Replacers\Replacer;
use Spatie\ResponseCache\ResponseCache;
use Spatie\ResponseCache\Events\CacheMissed;
use Symfony\Component\HttpFoundation\Response;
use Spatie\ResponseCache\Events\ResponseCacheHit;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class CacheResponse
{
    /** @var \Spatie\ResponseCache\ResponseCache */
    protected $responseCache;

    /** @var \Spatie\ResponseCache\CacheProfiles\CacheProfile */
    protected $cacheProfile;

    public function __construct(ResponseCache $responseCache, CacheProfile $cacheProfile)
    {
        $this->responseCache = $responseCache;
        $this->cacheProfile = $cacheProfile;
    }

    public function handle(Request $request, Closure $next, $lifetimeInSeconds = null): Response
    {
        if ($this->responseCache->enabled($request)) {
            if ($this->responseCache->hasBeenCached($request)) {
                event(new ResponseCacheHit($request));

                $response = $this->responseCache->getCachedResponseFor($request);

                if ($response->getContent()) {
                    collect(config('responsecache.replacers', []))->map(function ($replacerClass) {
                        return resolve($replacerClass);
                    })->each(function (Replacer $replacer) use ($request, $response) {
                        $cachedValue = $this->responseCache->getCachedKeyFor($request, $replacer->searchFor());
                        $response->setContent(str_replace($cachedValue, $replacer->replaceBy(), $response->getContent()));
                    });
                }

                return $response;
            }
        }

        $response = $next($request);

        if ($this->responseCache->enabled($request)) {
            if ($this->responseCache->shouldCache($request, $response)) {
                $this->responseCache->cacheResponse($request, $response, $lifetimeInSeconds);
            }
        }

        event(new CacheMissed($request));

        return $response;
    }
}
