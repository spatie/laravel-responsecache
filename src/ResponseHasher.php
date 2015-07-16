<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class ResponseHasher
{
    /**
     * @var CacheProfile
     */
    protected $cacheProfile;

    public function __construct(CacheProfile $cacheProfile)
    {
        $this->cacheProfile = $cacheProfile;
    }

    /**
     * Get a hash value for the given request.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getHashFor(Request $request)
    {
        return 'laravel-responsecache-'.md5(
            "{$request->getUri()}/{$request->getMethod()}/".$this->cacheProfile->cacheNameSuffix($request)
        );
    }
}
