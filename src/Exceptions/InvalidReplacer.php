<?php

namespace Spatie\ResponseCache\Exceptions;

use Exception;

class InvalidReplacer extends Exception
{
    public static function callbackString(): self
    {
        return new static('The replacer callback must return a string.');
    }
}
