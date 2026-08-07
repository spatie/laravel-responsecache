<?php

namespace Spatie\ResponseCache\Test\TestClasses;

use Illuminate\Support\Str;
use Spatie\ResponseCache\Attributes\NoCache;

class InvokableNoCacheController
{
    #[NoCache]
    public function __invoke(): string
    {
        return Str::random();
    }
}
