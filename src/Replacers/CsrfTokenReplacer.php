<?php

namespace Spatie\ResponseCache\Replacers;

class CsrfTokenReplacer implements Replacer
{
    public function searchFor(): string
    {
        return 'csrf-token';
    }

    public function replaceBy(): string
    {
        return csrf_token() ?? '';
    }
}
