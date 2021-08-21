<?php

namespace Spatie\ResponseCache\Hasher;

use Illuminate\Http\Request;
use Spatie\ResponseCache\ResponseCacheConfig;

class DefaultRequestHasher extends BaseRequestHasher
{
    public function getHashFor(Request $request, ResponseCacheConfig $cacheConfig): string
    {
        $cacheNameSuffix = $this->getCacheNameSuffix($request, $cacheConfig);

        return 'responsecache-'.md5(
                "{$request->getHost()}-{$request->getRequestUri()}-{$request->getMethod()}/$cacheNameSuffix"
            );
    }

    protected function getCacheNameSuffix(Request $request, ResponseCacheConfig $cacheConfig)
    {
        if ($request->attributes->has('responsecache.cacheNameSuffix')) {
            return $request->attributes->get('responsecache.cacheNameSuffix');
        }

        $cache_profile = $cacheConfig->cache_profile;

        return $cache_profile->useCacheNameSuffix($request, $cacheConfig);
    }
}
