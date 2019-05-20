<?php

namespace Spatie\ResponseCache\Hasher;

use Illuminate\Http\Request;

interface RequestHasher
{
    public function getHashFor(Request $request): string;
}
