<?php

namespace Spatie\ResponseCache\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class NoCache
{
}
