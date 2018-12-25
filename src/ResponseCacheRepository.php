<?php

namespace Spatie\ResponseCache;

use Illuminate\Cache\Repository;
use Symfony\Component\HttpFoundation\Response;

class ResponseCacheRepository
{
    /** @var \Illuminate\Cache\Repository */
    protected $cache;
    
    protected $cacheTag;

    /** @var \Spatie\ResponseCache\ResponseSerializer */
    protected $responseSerializer;

    public function __construct(ResponseSerializer $responseSerializer, Repository $cache)
    {
        $this->cache = $cache;
        $this->responseSerializer = $responseSerializer;
        $this->cacheTag = config('responsecache.cache_tag', 'responsecache');;
    }

    /**
     * @param string $cacheNameSuffix
     * @param string $key
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \DateTime|int $minutes
     */
    public function put(string $cacheNameSuffix, string $key, $response, $minutes)
    {
        $value = $this->responseSerializer->serialize($response);

        if (method_exists($this->cache->getStore(), 'tags')) {
            \Cache::tags([$this->cacheTag, $cacheNameSuffix])->put($key, $value, $minutes);
        } else {
            $this->cache->put($key, $value, $minutes);
        }
    }

    public function has(string $key, string $prefix = null): bool
    {
        if (method_exists($this->cache->getStore(), 'tags')) {
            return !is_null( \Cache::tags([$this->cacheTag, $prefix])->get($key));
        } else {
            return $this->cache->has($key);
        }
    }

    public function get(string $key, string $prefix = null): Response
    {
        if (method_exists($this->cache->getStore(), 'tags')) {
            $serializedResponse = \Cache::tags([$this->cacheTag, $prefix])->get($key);
        } else {
            $serializedResponse = $this->cache->get($key);
        }
        
        return $this->responseSerializer->unserialize($serializedResponse);
    }

    /**
     * @deprecated Use the new clear method, this is just an alias.
     */
    public function flush()
    {
        $this->clear();
    }

    public function clear()
    {
        $this->cache->flush();
    }

    public function forget(string $key = null, string $prefix = null): bool
    {
        if (method_exists($this->cache->getStore(), 'tags')) {
            \Cache::tags([$this->cacheTag, $prefix])->forget($key);
            return true;
        } else {
            return $this->cache->forget($key);
        }
    }
}
