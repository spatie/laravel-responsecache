<?php

namespace Spatie\ResponseCache\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void clear(array $tags = [])
 * @method static \Spatie\ResponseCache\ResponseCache forget(string | array $uris, array $tags = [])
 */
class ResponseCache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'responsecache';
    }
}
