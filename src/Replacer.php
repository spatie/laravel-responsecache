<?php

namespace Spatie\ResponseCache;

use Spatie\ResponseCache\Exceptions\InvalidReplacer;

class Replacer
{
    /** @var string */
    private $key;

    /** @var string */
    private $value;

    public function __construct(string $key, callable $callback)
    {
        if (! is_string($value = $callback())) {
            throw InvalidReplacer::callbackString();
        }

        $this->key = $key;
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}