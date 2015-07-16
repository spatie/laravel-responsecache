<?php
namespace Spatie\ResponseCache;
use Closure;
use Illuminate\Http\Request;
use Route;

class ResponseCacheMiddleware
{
    /**
     * Set the current page based on the page route parameter before the route's action is executed.
     *
     * @param Request $request
     * @param Closure $next
     * @return Request
     */
    public function handle(Request $request, Closure $next)
    {
        echo 'bla bla';
        return $next($request);
    }
}
