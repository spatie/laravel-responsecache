<?php

namespace Spatie\ResponseCache\Test\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrimResponseContent
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return response(
            trim($response->getContent()),
            $response->getStatusCode(),
            $response->headers->all(),
        );
    }
}
