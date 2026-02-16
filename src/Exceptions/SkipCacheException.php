<?php

namespace Spatie\ResponseCache\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class SkipCacheException extends RuntimeException
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct();
    }
}
