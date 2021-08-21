<?php

namespace Spatie\ResponseCache\CacheItemSelector;

use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\ResponseCacheConfig;
use Spatie\ResponseCache\ResponseCacheRepository;

class CacheItemSelector extends AbstractRequestBuilder
{
    protected array $urls;

    protected array $tags = [];

    public function __construct(
        protected ResponseCacheConfig $cacheConfig,
        protected ResponseCacheRepository $cache,
    ) {
    }

    public function usingTags(string | array $tags): static
    {
        $this->tags = is_array($tags) ? $tags : func_get_args();

        return $this;
    }

    public function forUrls(string | array $urls): static
    {
        $this->urls = is_array($urls) ? $urls : func_get_args();

        return $this;
    }

    public function forget(): void
    {
        collect($this->urls)
            ->map(function ($uri) {
                $request = $this->build($uri);

                return $this->cacheConfig->hasher->getHashFor($request,$this->cacheConfig);
            })
            ->filter(fn ($hash) => $this->taggedCache($this->tags)->has($hash))
            ->each(fn ($hash) => $this->taggedCache($this->tags)->forget($hash));
    }

    protected function taggedCache(array $tags = []): ResponseCacheRepository
    {
        return empty($tags)
            ? $this->cache
            : $this->cache->tags($tags);
    }
}
