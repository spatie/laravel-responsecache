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
        if ($request->attributes->has('responsecache.cacheNameSuffix')) {
            $cacheNameSuffix = $request->attributes->get('responsecache.cacheNameSuffix');
        } else {
            $cacheNameSuffix = $this->cacheProfile->useCacheNameSuffix($request);
        }

        return 'responsecache-' . md5(
            "{$request->getHost()}-{$request->getRequestUri()}-{$request->getMethod()}/$cacheNameSuffix"
        );
    }
}
