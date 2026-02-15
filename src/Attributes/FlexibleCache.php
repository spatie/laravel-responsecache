<?php

namespace Spatie\ResponseCache\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class FlexibleCache
{
    public function __construct(
        public int $fresh,
        public int $stale,
        public array $tags = [],
    ) {}
}
