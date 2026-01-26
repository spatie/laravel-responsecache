<?php

namespace Spatie\ResponseCache\Events;

use Illuminate\Http\Request;

class ResponseCacheHit
{
    public function __construct(
        public Request $request,
        public ?int $ageInSeconds = null,
        public ?array $tags = null,
    ) {
        //
    }
}
