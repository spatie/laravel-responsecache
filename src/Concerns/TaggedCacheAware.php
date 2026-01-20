<?php

namespace Spatie\ResponseCache\Concerns;

use Spatie\ResponseCache\ResponseCacheRepository;

trait TaggedCacheAware
{

    protected function taggedCache(array $tags = []): ResponseCacheRepository
    {
        if (empty($tags)) {
            return $this->cache;
        }

        return $this->cache->tags($tags);
    }
}
