<?php

namespace Spatie\ResponseCache\Facades;

use Illuminate\Support\Facades\Facade;

class ResponseCache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @see \Spatie\ResponseCache\ResponseCache
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'responsecache';
    }
}
