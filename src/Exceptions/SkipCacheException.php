<?php

namespace Spatie\ResponseCache\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class SkipCacheException extends RuntimeException implements ShouldntReport
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct();
    }
}
