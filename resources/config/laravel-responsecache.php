<?php

return [

    /**
     *  The given class will determinate if a request should be cached. The
     *  default class will cache all GET-requests.
     *
     *  You can provide your own class given that it implements the
     *  CacheProfile interface.
     */
    'cacheProfile' => Spatie\ResponseCache\CacheProfiles\CacheAllGetRequests::class,

    /**
     * When using the default CacheRequestFilter this setting controls the
     * number of minutes responses must be cached.
     */
    'cacheLifetimeInMinutes' => 5,

    /*
     * This setting determines if a http header named "Laravel-responsecache"
     * with the cache time should be added to a cached response. This
     * can be handy when debugging.
     */
    'addCacheTimeHeader' => true,

    /*
     * Here you may define the cache store that should be used to store
     * requests. This can be the name of any store that is
     * configured in app/config/cache.php
     */
     'cacheStore' => env('CACHE_DRIVER', 'file'),
];
