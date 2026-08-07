<?php

namespace Spatie\ResponseCache\Test\TestClasses;

use Illuminate\Support\Str;
use Spatie\ResponseCache\Attributes\Cache;

#[Cache(lifetime: 300)]
class ClassLevelCacheInvokableController
{
    public function __invoke(): string
    {
        return Str::random();
    }
}
