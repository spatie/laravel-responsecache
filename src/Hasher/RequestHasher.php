<?php

namespace Spatie\ResponseCache\Hasher;

use Illuminate\Http\Request;
use Spatie\ResponseCache\ResponseCacheConfig;

interface RequestHasher
{
    public function getHashFor(Request $request, ResponseCacheConfig $cacheConfig): string;
}
