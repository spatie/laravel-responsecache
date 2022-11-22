<?php

namespace Spatie\ResponseCache\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void clear()
 */
class ResponseCache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'responsecache';
    }
}
