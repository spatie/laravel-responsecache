<?php

namespace Spatie\ResponseCache\Middlewares;

use Closure;
use Illuminate\Http\Request;

class DoNotCacheResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->add(['responsecache.doNotCache' => true]);

        return $next($request);
    }
}
