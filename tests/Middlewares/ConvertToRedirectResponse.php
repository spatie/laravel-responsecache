<?php

namespace Spatie\ResponseCache\Test\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertToRedirectResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $next($request);

        return redirect('/accept-terms');
    }
}
