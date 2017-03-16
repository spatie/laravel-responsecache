<?php

namespace Spatie\ResponseCache\Events;

use Illuminate\Http\Request;

class CacheMissed
{
    public $request;

    /** @param \Illuminate\Http\Request $request */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
