<?php

namespace Spatie\ResponseCache\Events;

use Illuminate\Http\Request;

class CacheMissedEvent
{
    public function __construct(
        public Request $request,
    ) {
        //
    }
}
