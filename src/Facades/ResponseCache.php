<?php

namespace Spatie\ResponseCache\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void clear(array $tags = [])
 * @method static void forget(string|array $key, array $tags = [])
 * @method static bool enabled(\Illuminate\Http\Request $request)
 * @method static bool shouldCache(\Illuminate\Http\Request $request, \Symfony\Component\HttpFoundation\Response $response)
 * @method static bool shouldBypass(\Illuminate\Http\Request $request)
 * @method static \Symfony\Component\HttpFoundation\Response cacheResponse(\Illuminate\Http\Request $request, \Symfony\Component\HttpFoundation\Response $response, ?int $lifetimeInSeconds = null, array $tags = [])
 * @method static bool hasBeenCached(\Illuminate\Http\Request $request, array $tags = [])
 * @method static \Symfony\Component\HttpFoundation\Response getCachedResponseFor(\Illuminate\Http\Request $request, array $tags = [])
 * @method static \Spatie\ResponseCache\CacheItemSelector\CacheItemSelector selectCachedItems()
 */
class ResponseCache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'responsecache';
    }
}
