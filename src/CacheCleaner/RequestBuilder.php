<?php

namespace Spatie\ResponseCache\CacheCleaner;

use Illuminate\Http\Request;

class RequestBuilder extends AbstractRequestBuilder
{
    /**
     * @return Request
     */
    public function build(string $uri): Request
    {
        return $this->_build($uri);
    }
}
