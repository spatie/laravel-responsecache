<?php

namespace Spatie\ResponseCache\Exceptions;

use Exception;

class CouldntUnserialize extends Exception
{
    public static function serializedResponse(string $serializedResponse): self
    {
        return new static("Couldn't unserialize `{$serializedResponse}`");
    }
}
