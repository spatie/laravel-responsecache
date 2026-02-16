<?php

namespace Spatie\ResponseCache\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoNotCacheResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->add(['responsecache.doNotCache' => true]);

        return $next($request);
    }
}
