<?php

namespace Spatie\ResponseCache\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class FlexibleCache
{
    public int $fresh;

    public int $stale;

    public function __construct(
        public int $lifetime,
        public int $grace,
        public array $tags = [],
    ) {
        $this->fresh = $lifetime;
        $this->stale = $grace;
    }
}
