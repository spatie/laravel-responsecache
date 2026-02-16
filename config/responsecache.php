<?php

return [
    /*
     * Determine if the response cache middleware should be enabled.
     */
    'enabled' => env('RESPONSE_CACHE_ENABLED', true),

    /*
     * Cache configuration settings
     */
    'cache' => [
        /*
         * Here you may define the cache store that should be used to store
         * requests. This can be the name of any store that is
         * configured in app/config/cache.php
         */
        'store' => env('RESPONSE_CACHE_DRIVER', 'file'),

        /*
         * When using the default CacheRequestFilter this setting controls the
         * default number of seconds responses must be cached.
         */
        'lifetime_in_seconds' => (int) env('RESPONSE_CACHE_LIFETIME', 60 * 60 * 24 * 7),

        /*
         * If the cache driver you configured supports tags, you may specify a tag name
         * here. All responses will be tagged. When clearing the responsecache only
         * items with that tag will be flushed.
         *
         * You may use a string or an array here.
         */
        'tag' => env('RESPONSE_CACHE_TAG', ''),
    ],

    /*
     * Cache bypass header configuration
     */
    'bypass' => [
        /*
         * Whether cache bypass functionality is enabled
         */
        'enabled' => env('CACHE_BYPASS_HEADER_NAME') !== null,

        /*
         * The header name that will force a cache bypass.
         * This can be useful to monitor the performance of your application.
         */
        'header_name' => env('CACHE_BYPASS_HEADER_NAME'),

        /*
         * The header value that will force a cache bypass.
         */
        'header_value' => env('CACHE_BYPASS_HEADER_VALUE'),
    ],

    /*
     * Debug and development settings
     */
    'debug' => [
        /*
         * This setting determines if a http header named with the cache time
         * should be added to a cached response. This can be handy when
         * debugging.
         */
        'add_time_header' => env('APP_DEBUG', false),

        /*
         * This setting determines the name of the http header that contains
         * the time at which the response was cached
         */
        'time_header_name' => env('RESPONSE_CACHE_HEADER_NAME', 'laravel-responsecache'),

        /*
         * This setting determines if a http header named with the cache age
         * should be added to a cached response. This can be handy when
         * debugging.
         * ONLY works when "add_time_header" is also active!
         */
        'add_age_header' => env('RESPONSE_CACHE_AGE_HEADER', false),

        /*
         * This setting determines the name of the http header that contains
         * the age of cache
         */
        'age_header_name' => env('RESPONSE_CACHE_AGE_HEADER_NAME', 'laravel-responsecache-age'),
    ],

    /*
     * Query parameters listed here will be ignored when generating the cache key.
     * This is useful for tracking parameters like UTM tags or gclid that don't
     * affect the page content but would otherwise create separate cache entries.
     */
    'ignored_query_parameters' => [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
    ],

    /*
     * Customization - Class implementations
     */

    /*
     *  The given class will determinate if a request should be cached. The
     *  default class will cache all successful GET-requests.
     *
     *  You can provide your own class given that it implements the
     *  CacheProfile interface.
     */
    'cache_profile' => Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests::class,

    /*
     * This class is responsible for generating a hash for a request. This hash
     * is used to look up a cached response.
     */
    'hasher' => \Spatie\ResponseCache\Hasher\DefaultHasher::class,

    /*
     * This class is responsible for serializing responses.
     */
    'serializer' => \Spatie\ResponseCache\Serializers\JsonSerializer::class,

    /*
     * Here you may define replacers that dynamically replace content from the response.
     * Each replacer must implement the Replacer interface.
     */
    'replacers' => [
        \Spatie\ResponseCache\Replacers\CsrfTokenReplacer::class,
    ],
];
