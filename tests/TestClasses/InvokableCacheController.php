<?php

namespace Spatie\ResponseCache\Test\TestClasses;

use Illuminate\Support\Str;
use Spatie\ResponseCache\Attributes\Cache;

class InvokableCacheController
{
    #[Cache(lifetime: 300)]
    public function __invoke(): string
    {
        return Str::random();
    }
}
