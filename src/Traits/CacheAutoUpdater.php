<?php

namespace Spatie\ResponseCache\Traits;

use Spatie\ResponseCache\Facades\ResponseCache;

trait CacheAutoUpdater
{
    public static function bootCacheAutoUpdater()
    {
        self::created(function ($model) {
            ResponseCache::clear();
        });

        self::updated(function ($model) {
            ResponseCache::clear();
        });

        self::deleted(function ($model) {
            ResponseCache::clear();
        });
    }
}

