<?php

namespace Spatie\ResponseCache\Replacers;

interface Replacer
{
    public function searchFor(): string;

    public function replaceBy(): string;
}
