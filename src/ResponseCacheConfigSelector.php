<?php

namespace Spatie\ResponseCache;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\ResponseCache\Middlewares\CacheResponse;

class ResponseCacheConfigSelector
{
    public static function getConfig(): string
    {
        $middleware = collect(Route::current()->middleware())
            ->filter(fn($middleware) => is_string($middleware))
            ->last(fn($middleware) => Str::startsWith($middleware, 'cacheResponse') || Str::startsWith($middleware,
                    CacheResponse::class));

        if (!$middleware) {
            return 'default';
        }

        $args = explode(':', $middleware)[1] ?? 'default';

        return Str::before($args, ',');
    }
}
