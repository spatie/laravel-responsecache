<?php

namespace Spatie\ResponseCache\Events;

use Illuminate\Http\Request;

class CacheMissed
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
