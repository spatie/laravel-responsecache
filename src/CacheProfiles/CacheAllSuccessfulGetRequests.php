<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheAllSuccessfulGetRequests extends BaseCacheProfile implements CacheProfile
{
    public function shouldCacheRequest(Request $request): bool
    {
        if ($request->ajax()) {
            return false;
        }

        if ($this->isRunningInConsole()) {
            return false;
        }
        return $request->isMethod('get');
    }

    public function shouldCacheResponse(Response $response): bool
    {
        return $response->isSuccessful() || $response->isRedirection();
    }
}
