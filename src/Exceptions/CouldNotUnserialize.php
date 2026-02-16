<?php

namespace Spatie\ResponseCache\Exceptions;

use Exception;

class CouldNotUnserialize extends Exception
{
    public static function serializedResponse(string $serializedResponse): self
    {
        return new self("Could not unserialize serialized response `{$serializedResponse}`");
    }
}
