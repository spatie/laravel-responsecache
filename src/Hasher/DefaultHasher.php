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
        $queryString = $this->getNormalizedQueryString($request);

        if ($queryString !== '') {
            $queryString = '?'.$queryString;
        }

        return $request->getBaseUrl().$request->getPathInfo().$queryString;
    }

    protected function getNormalizedQueryString(Request $request): string
    {
        $queryString = $request->getQueryString();

        if ($queryString === null || $queryString === '') {
            return '';
        }

        $ignoredParameters = config('responsecache.ignored_query_parameters', []);

        if (empty($ignoredParameters)) {
            return $queryString;
        }

        parse_str($queryString, $parameters);

        $parameters = array_diff_key($parameters, array_flip($ignoredParameters));

        if (empty($parameters)) {
            return '';
        }

        return http_build_query($parameters);
    }

    protected function getCacheNameSuffix(Request $request): string
    {
        if ($request->attributes->has('responsecache.cacheNameSuffix')) {
            return $request->attributes->get('responsecache.cacheNameSuffix');
        }

        return $this->cacheProfile->useCacheNameSuffix($request);
    }
}
