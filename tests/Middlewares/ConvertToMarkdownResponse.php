<?php

namespace Spatie\ResponseCache\Test\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertToMarkdownResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->header('Accept') !== 'text/markdown') {
            return $response;
        }

        return response('# page content', 200, ['Content-Type' => 'text/markdown']);
    }
}
