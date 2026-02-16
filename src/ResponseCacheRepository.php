<?php

namespace Spatie\ResponseCache;

use Closure;
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

    public function put(string $key, Response $response, int $seconds): void
    {
        $this->cache->put($key, $this->responseSerializer->serialize($response), now()->addSeconds($seconds));
    }

    /**
     * Get a cached response using flexible/SWR strategy.
     *
     * @param  array{0: int, 1: int}  $seconds  [fresh_seconds, total_seconds]
     * @param  Closure  $callback  Callback that returns a Response object
     */
    public function flexible(string $key, array $seconds, Closure $callback): Response
    {
        $result = $this->cache->flexible(
            $key,
            $seconds,
            function () use ($callback) {
                $response = $callback();

                return $this->responseSerializer->serialize($response);
            },
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
     *
     * @return bool Whether the cache was cleared successfully.
     */
    public function clear(): bool
    {
        if ($this->isTagged($this->cache)) {
            return $this->cache->flush();
        }

        if (empty(config('responsecache.cache.tag'))) {
            return $this->cache->clear();
        }

        return $this->cache->tags(config('responsecache.cache.tag'))->flush();
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($key);
    }

    public function tags(array $tags): self
    {
        if ($this->cache instanceof TaggedCache) {
            $tags = array_merge($this->cache->getTags()->getNames(), $tags);
        }

        return new self($this->responseSerializer, $this->cache->tags($tags));
    }

    public function isTagged(mixed $repository): bool
    {
        return $repository instanceof TaggedCache;
    }
}
