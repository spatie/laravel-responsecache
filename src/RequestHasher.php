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
        return 'responsecache-'.md5(
            "{$request->getRequestUri()}/{$request->getMethod()}/".$this->cacheProfile->cacheNameSuffix($request)
        );
    }
}
