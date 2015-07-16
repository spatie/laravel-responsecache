<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;

class ResponseHasher
{
    /**
     * Get a hash value for the given request.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getHashFor(Request $request)
    {
        return 'laravel-responsecache-'.md5("{$request->getUri()}/{$request->getMethod()}");
    }
}
