<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class RequestHasher
{
    /** @var \Spatie\ResponseCache\CacheProfiles\CacheProfile */
    protected $cacheProfile;

    public function __construct(CacheProfile $cacheProfile)
    {
        $this->cacheProfile = $cacheProfile;
    }

    public function getHashFor(Request $request): string
    {
        $cacheNameSuffix = $this->cacheProfile->cacheNameSuffix($request);

        return $cacheNameSuffix . '-responsecache-'.md5(
                "{$request->getRequestUri()}/{$request->getMethod()}/". $cacheNameSuffix
            );
    }
}
