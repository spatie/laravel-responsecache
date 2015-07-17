<?php

namespace Spatie\ResponseCache;

use Illuminate\Contracts\Cache\Repository;

class ResponseCacheRepository
{
    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var ResponseSerializer
     */
    protected $responseSerializer;

    /**
     * @param Repository         $cache
     * @param ResponseSerializer $responseSerializer
     */
    public function __construct(Repository $cache, ResponseSerializer $responseSerializer)
    {
        $this->cache = $cache;
        $this->responseSerializer = $responseSerializer;
    }

    public function put($key, $response, $minutes)
    {
        $this->cache->put($key, $this->responseSerializer->serialize($response), $minutes);
    }

    public function has($key)
    {
        return $this->cache->has($key);
    }

    public function get($key)
    {
        return $this->responseSerializer->unserialize($this->cache->get($key));
    }
}
