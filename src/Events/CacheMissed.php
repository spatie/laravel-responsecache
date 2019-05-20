<?php

namespace Spatie\ResponseCache\Events;

use Illuminate\Http\Request;

class CacheMissed
{
    /** @var \Illuminate\Http\Request */
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
