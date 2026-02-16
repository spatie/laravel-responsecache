<?php

namespace Spatie\ResponseCache\Configuration;

class FlexibleCacheConfiguration
{
    public function __construct(
        public int $lifetime,
        public int $grace,
        public array $tags = [],
    ) {}
}
