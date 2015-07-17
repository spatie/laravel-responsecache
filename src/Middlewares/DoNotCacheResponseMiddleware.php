<?php

namespace Spatie\ResponseCache\Middlewares;

use Illuminate\Http\Request;
use Closure;

class DoNotCacheResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->add(['laravel-cacheresponse.doNotCache' => true]);

        return $next($request);
    }
}
