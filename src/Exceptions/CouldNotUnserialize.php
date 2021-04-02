<?php

namespace Spatie\ResponseCache\Exceptions;

use Exception;

class CouldNotUnserialize extends Exception
{
    public static function serializedResponse(string $serializedResponse): static
    {
        return new static("Could not unserialize serialized response `{$serializedResponse}`");
    }
}
