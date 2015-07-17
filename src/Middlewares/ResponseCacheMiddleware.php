<?php

namespace Spatie\ResponseCache\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Spatie\ResponseCache\ResponseCache;

class ResponseCacheMiddleware
{
    /**
     * @var \Spatie\ResponseCache\ResponseCache
     */
    protected $responseCache;

    public function __construct(ResponseCache $responseCache)
    {
        $this->responseCache = $responseCache;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return Request
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->responseCache->hasCached($request)) {
            return $this->responseCache->getCachedResponseFor($request);
        }

        $response = $next($request);

        if ($this->responseCache->shouldCache($request, $response)) {
            $this->responseCache->cacheResponse($request, $response);
        }

        return $response;
    }
}
