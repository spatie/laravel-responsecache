<?php

namespace Spatie\ResponseCache\Replacers;

use Symfony\Component\HttpFoundation\Response;

interface Replacer
{
    /*
     * Transform the initial response before it gets cached.
     *
     * For example: replace a generated csrf_token by '<csrf-token-here>' that you can
     * replace with its dynamic counterpart when the cached response is returned.
     */
    public function transformInitialResponse(Response $response): void;

    /*
     * Replace any data you want in the cached response before it gets
     * sent to the browser.
     *
     * For example: replace '<csrf-token-here>' by a call to csrf_token()
     */
    public function replaceCachedResponse(Response $response): void;
}