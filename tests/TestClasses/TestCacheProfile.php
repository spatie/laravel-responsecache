<?php

namespace Spatie\ResponseCache\Test\TestClasses;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\BaseCacheProfile;
use Spatie\ResponseCache\ResponseCacheConfig;
use Symfony\Component\HttpFoundation\Response;

class TestCacheProfile extends BaseCacheProfile
{
    public function shouldCacheRequest(Request $request, ResponseCacheConfig $cacheConfig): bool
    {
        return true;
    }

    public function shouldCacheResponse(Response $response, ResponseCacheConfig $cacheConfig): bool
    {
        return true;
    }

    public function useCacheNameSuffix(Request $request, ResponseCacheConfig $cacheConfig): string
    {
        return 'cacheProfileSuffix';
    }
}



