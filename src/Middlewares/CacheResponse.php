<?php

namespace Spatie\ResponseCache\Middlewares;

use Closure;
use Illuminate\Http\Request;
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
                    foreach (config('responsecache.replacers', []) as $replacerClass) {
                        $replacer = resolve($replacerClass);
                        if ($replacer instanceof \Spatie\ResponseCache\Replacers\ReplacerInterface) {
                            $cachedValue = $this->responseCache->getCachedKeyFor($request, $replacer->getKey());
                            $response->setContent(str_replace($cachedValue, $replacer->getValue(), $response->getContent()));
                        }
                    }
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
