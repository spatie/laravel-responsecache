<?php

namespace Spatie\ResponseCache\CacheRequestFilters;

use Illuminate\Http\Request;

class CacheAllGetRequests implements CacheRequestFilter
{
    /**
     * Determine if the given request should be cached;.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function shouldBeCached(Request $request)
    {
        return $request->isMethod('get');
    }
}
