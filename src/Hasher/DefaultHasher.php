<?php

namespace Spatie\ResponseCache\Hasher;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class DefaultHasher implements RequestHasher
{
    public function __construct(
        protected CacheProfile $cacheProfile,
    ) {
        //
    }

    public function getHashFor(Request $request): string
    {
        $cacheNameSuffix = $this->getCacheNameSuffix($request);

        return 'responsecache-' . md5(
            "{$request->getHost()}-{$request->getRequestUri()}-{$request->getMethod()}/$cacheNameSuffix"
        );
    }

    protected function getCacheNameSuffix(Request $request)
    {
        if ($request->attributes->has('responsecache.cacheNameSuffix')) {
            return $request->attributes->get('responsecache.cacheNameSuffix');
        }

        return $this->cacheProfile->useCacheNameSuffix($request);
    }
}
