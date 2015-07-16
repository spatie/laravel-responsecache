<?php
namespace Spatie\ResponseCache;
use Closure;
use Illuminate\Http\Request;
use Route;

class ResponseCacheMiddleware
{
    /**
     * @var ResponseCache
     */
    protected $responseCache;

    public function __construct(ResponseCache $responseCache)
    {
        $this->responseCache = $responseCache;
    }

    /**
     *
     * @param Request $request
     * @param Closure $next
     * @return Request
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->responseCache->hasCached($request)) {
            echo 'cached';
             die($this->responseCache->getCachedResponseFor($request));

        }

        $response = $next($request);

        if ($this->responseCache->shouldCache($request)) {
            $this->responseCache->cacheResponse($request, $response);
        }

        return $response;
    }
}
