<?php

namespace Spatie\ResponseCache\Replacers;

interface ReplacerInterface
{
    public function getKey(): string;

    public function getValue(): string;
}
