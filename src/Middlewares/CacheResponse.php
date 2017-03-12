<?php

namespace Spatie\ResponseCache\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Spatie\ResponseCache\ResponseCache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /** @var \Spatie\ResponseCache\ResponseCache */
    protected $responseCache;

    public function __construct(ResponseCache $responseCache)
    {
        $this->responseCache = $responseCache;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->responseCache->hasBeenCached($request)) {
            return $this->responseCache->getCachedResponseFor($request);
        }

        $response = $next($request);

        if ($this->responseCache->shouldCache($request, $response)) {
            $this->responseCache->cacheResponse($request, $response);
        }

        return $response;

    }
}
