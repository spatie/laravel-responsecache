<?php

namespace Spatie\ResponseCache\CacheCleaner;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\ResponseCacheRepository;

class CacheCleaner extends AbstractRequestBuilder
{
    public function __construct(
        protected RequestHasher $hasher,
        protected ResponseCacheRepository $cache,
    ) {
    }

    /**
     * @return void
     */
    public function forget(string | array $uris,  $tags = [])
    {
        if (!is_array($uris)) {
            $uris = [$uris];
        }

        $cache = $this->cache;
        if (!empty($tags)) {
            $cache = $cache->tags($tags);
        }

        collect($uris)->map(function ($uri) {
            $request = $this->_build($uri);
            $hash = $this->hasher->getHashFor($request);
            return $hash;
        })->each(function ($hash) use ($cache) {
            if ($cache->has($hash)) {
                $cache->forget($hash);
            }
        });
    }

}
