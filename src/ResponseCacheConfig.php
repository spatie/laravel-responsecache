<?php

namespace Spatie\ResponseCache;

use Spatie\ResponseCache\CacheProfiles\BaseCacheProfile;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Exceptions\InvalidConfig;
use Spatie\ResponseCache\Hasher\BaseRequestHasher;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\Replacers\Replacer;
use Spatie\ResponseCache\Serializers\BaseSerializer;
use Spatie\ResponseCache\Serializers\Serializer;

class ResponseCacheConfig
{
    /**
     * This package supports multiple cache profiles.
     */
    public string $name;

    /*
     * Determine if the response cache middleware should be enabled.
     */
    public bool $enabled;

    /**
     *  This setting will determinate if a request should be cached.
     */
    public CacheProfile $cache_profile;

    /**
     * This setting controls the default number of seconds responses must be cached.
     */
    public int $cache_lifetime_in_seconds;

    /**
     * This setting determines if a http header named with the cache time
     * should be added to a cached response. This can be handy when
     * debugging.
     */
    public bool $add_cache_time_header;

    /**
     * This setting determines the name of the http header that contains
     * the time at which the response was cached
     */
    public string $cache_time_header_name;

    /**
     * This setting determines the name of the cache store
     * that should be used to store requests.
     */
    public string $cache_store;

    /**
     * Here you may define replacers that dynamically replace content from the response.
     * Each replacer must implement the Replacer interface.
     *
     * @param  Replacer[]
     */
    public array $replacers = [];

    /**
     * If the cache driver you configured supports tags, you may specify a tag name
     * here. All responses will be tagged. When clearing the responsecache only
     * items with that tag will be flushed.
     *
     * You may use a string or an array here.
     */
    public string $cache_tag;

    /**
     * This class is responsible for generating a hash for a request. This hash
     * is used to look up for a cached response.
     */
    public RequestHasher $hasher;

    /**
     * This class is responsible for serializing responses.
     */
    public Serializer $serializer;

    /**
     * @throws InvalidConfig
     */
    public function __construct(array $properties)
    {
        $this->name = $properties['name'] ?? 'default';
        $this->enabled = (bool) $properties['enabled'];
        $this->cache_tag = $properties['cache_tag'];
        $this->cache_store = $properties['cache_store'];
        $this->cache_time_header_name = $properties['cache_time_header_name'];
        $this->add_cache_time_header = (bool) $properties['add_cache_time_header'];
        $this->cache_lifetime_in_seconds = (int) $properties['cache_lifetime_in_seconds'];
        $this->replacers = $properties['replacers'] ?? [];

        $this->setupCacheProfile($properties);
        $this->setupRequestHasher($properties);
        $this->setupSerializer($properties);
    }

    /**
     * Set up a cache profile class.
     * @throws InvalidConfig
     */
    private function setupCacheProfile(array $config)
    {
        $cache_profile_class = $config['cache_profile'];

        if (!is_subclass_of($cache_profile_class, BaseCacheProfile::class)) {
            throw InvalidConfig::invalidCacheProfile($cache_profile_class);
        }

        $this->cache_profile = app($cache_profile_class);
    }

    /**
     * Set up a request hasher class.
     * @throws InvalidConfig
     */
    private function setupRequestHasher(array $config)
    {
        $request_hasher_class = $config['hasher'];

        if (!is_subclass_of($request_hasher_class, BaseRequestHasher::class)) {
            throw InvalidConfig::invalidRequestHasher($request_hasher_class);
        }

        $this->hasher = app($request_hasher_class);
    }

    /**
     * Set up a request hasher class.
     * @throws InvalidConfig
     */
    private function setupSerializer(array $config)
    {
        $serializer = $config['serializer'];

        if (!is_subclass_of($serializer, BaseSerializer::class)) {
            throw InvalidConfig::invalidRequestHasher($serializer);
        }

        $this->serializer = app($serializer);
    }
}
