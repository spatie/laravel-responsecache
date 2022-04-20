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

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function get(string $key): Response
    {
        return $this->responseSerializer->unserialize($this->cache->get($key) ?? '');
    }

    public function clear(): void
    {
        if ($this->isTagged($this->cache)) {
            $this->cache->flush();

            return;
        }

        if (empty(config('responsecache.cache_tag'))) {
            $this->cache->clear();

            return;
        }

        $this->cache->tags(config('responsecache.cache_tag'))->flush();
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
