<?php

namespace Spatie\ResponseCache;

use Illuminate\Contracts\Config\Repository as Repository;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpFoundation\Response;

class ResponseCacheRepository
{
    /** @var \Illuminate\Cache\Repository */
    protected $cache;

    /** @var \Spatie\ResponseCache\ResponseSerializer */
    protected $responseSerializer;

    /** @var string */
    protected $cacheStoreName;

    public function __construct(Application $app, ResponseSerializer $responseSerializer, Repository $config)
    {
        $this->cache = $app['cache']->store($config->get('responsecache.cacheStore'));
        $this->responseSerializer = $responseSerializer;
    }

    /**
     * @param string $key
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \DateTime|int $minutes
     */
    public function put(string $key, $response, $minutes)
    {
        $this->cache->put($key, $this->responseSerializer->serialize($response), $minutes);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function get(string $key): Response
    {
        return $this->responseSerializer->unserialize($this->cache->get($key));
    }

    public function flush()
    {
        $this->cache->flush();
    }
}
