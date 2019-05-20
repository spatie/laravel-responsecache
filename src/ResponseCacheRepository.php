<?php

namespace Spatie\ResponseCache;

use Illuminate\Cache\Repository;
use Illuminate\Cache\TaggedCache;
use Symfony\Component\HttpFoundation\Response;

class ResponseCacheRepository
{
    /** @var \Illuminate\Cache\Repository */
    protected $cache;

    /** @var \Spatie\ResponseCache\ResponseSerializer */
    protected $responseSerializer;

    public function __construct(ResponseSerializer $responseSerializer, Repository $cache)
    {
        $this->cache = $cache;

        $this->responseSerializer = $responseSerializer;
    }

    /**
     * @param string $key
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \DateTime|int $seconds
     */
    public function put(string $key, $response, $seconds)
    {
        $this->cache->put($key, $this->responseSerializer->serialize($response), $seconds);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function get(string $key): Response
    {
        return $this->responseSerializer->unserialize($this->cache->get($key));
    }

    public function clear()
    {
        $this->cache->clear();
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($key);
    }

    /**
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\TaggedCache
     */
    public function tags($names): TaggedCache
    {
        return $this->cache->tags($names);
    }
}
