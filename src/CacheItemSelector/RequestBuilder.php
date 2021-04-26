<?php

namespace Spatie\ResponseCache\CacheItemSelector;

use Illuminate\Http\Request;

class RequestBuilder extends AbstractRequestBuilder
{
    public function build(string $uri): Request
    {
        return $this->_build($uri);
    }
}
