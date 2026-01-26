<?php

namespace Spatie\ResponseCache\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Cache
{
    public function __construct(
        public ?int $lifetime = null,
        public array $tags = [],
        public ?string $driver = null,
    ) {
    }
}
