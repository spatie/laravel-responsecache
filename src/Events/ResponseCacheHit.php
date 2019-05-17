<?php

namespace Spatie\ResponseCache\Events;

use Illuminate\Http\Request;

class ResponseCacheHit
{
    /** @var \Illuminate\Http\Request */
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
