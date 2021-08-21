<?php

namespace Spatie\ResponseCache;

use DateTime;
use Illuminate\Cache\Repository;
use Illuminate\Cache\TaggedCache;
use Symfony\Component\HttpFoundation\Response;

class ResponseCacheRepository
{
    public function __construct(
        protected ResponseCacheConfig $config,
        protected Repository $cache,
    ) {
        //
    }

    public function put(string $key, Response $response, DateTime|int $seconds): void
    {
        $this->cache->put($key, $this->config->serializer->serialize($response),
            is_numeric($seconds) ? now()->addSeconds($seconds) : $seconds);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function get(string $key): Response
    {
        return $this->config->serializer->unserialize($this->cache->get($key));
    }

    public function clear(): void
    {
        if ($this->isTagged($this->cache)) {
            $this->cache->flush();

            return;
        }

        if (empty($this->config->cache_tag)) {
            $this->cache->clear();

            return;
        }

        $this->cache->tags($this->config->cache_tag)->flush();
    }

    public function isTagged($repository): bool
    {
        return $repository instanceof TaggedCache && !empty($repository->getTags());
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

        return new self($this->config, $this->cache->tags($tags));
    }
}
