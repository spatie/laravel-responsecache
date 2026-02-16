<?php

namespace Spatie\ResponseCache\Facades;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Spatie\ResponseCache\CacheItemSelector\CacheItemSelector;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method static bool clear(array $tags = [])
 * @method static \Spatie\ResponseCache\ResponseCache forget(string|array $uris, array $tags = [])
 * @method static bool enabled(Request $request)
 * @method static bool shouldCache(Request $request, Response $response)
 * @method static bool shouldBypass(Request $request)
 * @method static Response cacheResponse(Request $request, Response $response, ?int $lifetimeInSeconds = null, array $tags = [])
 * @method static bool hasBeenCached(Request $request, array $tags = [])
 * @method static Response getCachedResponseFor(Request $request, array $tags = [])
 * @method static CacheItemSelector selectCachedItems()
 * @method static Response flexible(string $key, array $seconds, Closure $callback, array $tags = [])
 */
class ResponseCache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'responsecache';
    }
}
