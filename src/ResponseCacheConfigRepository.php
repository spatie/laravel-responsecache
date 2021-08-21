<?php

namespace Spatie\ResponseCache;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ResponseCacheConfigRepository
{
    protected array $configs;

    public function addConfig(ResponseCacheConfig $responseCacheConfig)
    {
        $this->configs[$responseCacheConfig->name] = $responseCacheConfig;
    }

    public function getConfig(string $name): ?ResponseCacheConfig
    {
        return $this->configs[$name] ?? null;
    }
}
