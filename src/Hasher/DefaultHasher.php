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
        $strings = [
            'responsecache',
            $request->getHost(),
            $this->getNormalizedRequestUri($request),
            $request->getMethod(),
            $this->getCacheNameSuffix($request),
        ];

        return hash('xxh128', implode('-', $strings));
    }

    protected function getNormalizedRequestUri(Request $request): string
    {
        if ($queryString = $request->getQueryString()) {
            $queryString = '?'.$queryString;
        }

        return $request->getBaseUrl().$request->getPathInfo().$queryString;
    }

    protected function getCacheNameSuffix(Request $request)
    {
        if ($request->attributes->has('responsecache.cacheNameSuffix')) {
            return $request->attributes->get('responsecache.cacheNameSuffix');
        }

        return $this->cacheProfile->useCacheNameSuffix($request);
    }
}
