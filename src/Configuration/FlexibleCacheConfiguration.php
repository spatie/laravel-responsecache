<?php

namespace Spatie\ResponseCache\Configuration;

class FlexibleCacheConfiguration
{
    public function __construct(
        public int $fresh,
        public int $stale,
        public array $tags = [],
    ) {
    }
}
