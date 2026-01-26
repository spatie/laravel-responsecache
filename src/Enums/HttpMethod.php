<?php

namespace Spatie\ResponseCache\Enums;

enum HttpMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Patch = 'PATCH';
    case Delete = 'DELETE';
    case Head = 'HEAD';
    case Options = 'OPTIONS';

    public static function fromString(string $method): self
    {
        return self::from(strtoupper($method));
    }
}
