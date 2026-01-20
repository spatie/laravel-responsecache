<?php

namespace Spatie\ResponseCache;

use Illuminate\Cache\Repository;
use Illuminate\Cache\TaggedCache;
use Spatie\ResponseCache\Serializers\Serializer;
use Symfony\Component\HttpFoundation\Response;

class ResponseCacheRepository
{
    public function __construct(
        protected Serializer $responseSerializer,
        protected Repository $cache,
    ) {
        //
    }

    public function put(string $key, Response $response, \DateTime | int $seconds): void
    {
        $this->cache->put($key, $this->responseSerializer->serialize($response), is_numeric($seconds) ? now()->addSeconds($seconds) : $seconds);
    }

    /**
     * Get a cached response using flexible/SWR strategy.
     *
     * @param string $key
     * @param array{0: int, 1: int} $seconds [fresh_seconds, total_seconds]
     * @param \Closure $callback Callback that returns a Response object
     * @param bool|null $defer
     * @return Response
     */
    public function flexible(string $key, array $seconds, \Closure $callback, ?bool $defer = false): Response
    {
        $result = $this->cache->flexible(
            $key,
            $seconds,
            function () use ($callback) {
                $response = $callback();

                return $this->responseSerializer->serialize($response);
            },
            null,
            $defer
        );

        return $this->responseSerializer->unserialize($result);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function get(string $key): Response
    {
        return $this->responseSerializer->unserialize($this->cache->get($key) ?? '');
    }

    /**
     * If the response cache tag is empty, or a Store doesn't support tags, the whole cache will be cleared.

     * @return bool Whether the cache was cleared successfully.
     */
    public function clear(): bool
    {
        if ($this->isTagged($this->cache)) {
            return $this->cache->flush();
        }

        if (empty(config('responsecache.cache_tag'))) {
            return $this->cache->clear();
        }

        return $this->cache->tags(config('responsecache.cache_tag'))->flush();
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($key);
    }

    public function tags(array $tags): self
    {
        if ($this->isTagged($this->cache)) {
            $tags = array_merge($this->cache->getTags()->getNames(), $tags);
        }

        return new self($this->responseSerializer, $this->cache->tags($tags));
    }

    public function isTagged($repository): bool
    {
        return $repository instanceof TaggedCache && ! empty($repository->getTags());
    }
}
