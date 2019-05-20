<?php

namespace Spatie\ResponseCache\Replacers;

use Symfony\Component\HttpFoundation\Response;

interface Replacer
{
    /*
     * Prepare the initial response before it gets cached.
     */
    public function prepareResponseToCache(Response $response): void;

    /*
     * Replace any data you want in the cached response before it gets sent.
     */
    public function replaceInCachedResponse(Response $response): void;
}
