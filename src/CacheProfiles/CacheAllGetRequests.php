<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Illuminate\Http\Request;

class CacheAllGetRequests extends BaseCacheProfile implements CacheProfile
{
    /**
     * Determine if the given request should be cached;.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function shouldBeCached(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            return false;
        }

        if ($this->app->runningInConsole()) {
            return false;
        }

        return $request->isMethod('get');
    }

}
