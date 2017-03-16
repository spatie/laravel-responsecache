<?php

namespace Spatie\ResponseCache\Events;


class ServedCachedResponse
{
    public $request;

    /** @param  Request  $request */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
