<?php

namespace Spatie\ResponseCache\Test\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChangeCacheNameSuffixAfterResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $request->attributes->set('responsecache.cacheNameSuffix', 'changed');

        return $response;
    }
}
