<?php

namespace Spatie\ResponseCache\Test\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertToServerErrorResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request)->setStatusCode(500);
    }
}
