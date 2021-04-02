<?php

namespace Spatie\ResponseCache\Events;

use Illuminate\Http\Request;

class CacheMissed
{
    public function __construct(
        public Request $request,
    ) {
        //
    }
}
