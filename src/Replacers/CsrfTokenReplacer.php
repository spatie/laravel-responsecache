<?php

namespace Spatie\ResponseCache\Replacers;

class CsrfTokenReplacer implements ReplacerInterface
{
    public function getKey(): string
    {
        return 'csrf-token';
    }

    public function getValue(): string
    {
        return csrf_token() ?? '';
    }
}
