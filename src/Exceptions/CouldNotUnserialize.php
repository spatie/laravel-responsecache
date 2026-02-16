<?php

namespace Spatie\ResponseCache\Exceptions;

use Exception;

class CouldNotUnserialize extends Exception
{
    public static function serializedResponse(string $serializedResponse): self
    {
        $truncated = mb_substr($serializedResponse, 0, 200);

        if (mb_strlen($serializedResponse) > 200) {
            $truncated .= '... (truncated)';
        }

        return new self("Could not unserialize serialized response `{$truncated}`");
    }
}
