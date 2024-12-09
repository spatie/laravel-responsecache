<?php

namespace Spatie\ResponseCache\Replacers;

use Symfony\Component\HttpFoundation\Response;

class CsrfTokenReplacer implements Replacer
{
    protected string $replacementString = '<laravel-responsecache-csrf-token-here>';

    public function prepareResponseToCache(Response $response): void
    {
        $csrf_token = csrf_token();
        $content = $response->getContent();

        if (! $content || ! $csrf_token) {
            return;
        }

        $response->setContent(str_replace(
            $csrf_token,
            $this->replacementString,
            $content,
        ));
    }

    public function replaceInCachedResponse(Response $response): void
    {
        $csrf_token = csrf_token();
        $content = $response->getContent();

        if (! $content || ! $csrf_token) {
            return;
        }

        $response->setContent(str_replace(
            $this->replacementString,
            $csrf_token,
            $content,
        ));
    }
}
