<?php
return [

    /**
     *  This class will determinate if a request should be cached. The
     *  default class will cache all GET-requests.
     *
     *  You can provide your own class given that it implements the
     *  RequestFilter interface.
     */
    'cacheProfile' => Spatie\ResponseCache\CacheProfiles\CacheAllGetRequests::class,

    /**
     * When using the default CacheRequestFilter this setting controls the
     * number of minutes responses must be cached. You may  also pass a
     * PHP DateTime instance representing the expiration time of the
     * cached item.
     *
     */
    'cacheLifetimeInMinutes' => 5,

    /*
     * This setting determines if a http header named "Laravel-reponsecache"
     * with the cache time should be added to a cached response.
     */
    'addCacheTimeHeader' => true
];
