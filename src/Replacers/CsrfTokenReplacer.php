<?php

namespace Spatie\ResponseCache\Replacers;

use Symfony\Component\HttpFoundation\Response;

class CsrfTokenReplacer implements Replacer
{
    protected string $replacementString = '<laravel-responsecache-csrf-token-here>';

    public function prepareResponseToCache(Response $response): void
    {
        $content = $response->getContent();

        if (! $content) {
            return;
        }

        $csrfToken = csrf_token();

        if (! $csrfToken) {
            return;
        }

        $parts = explode($csrfToken, $content);

        if (count($parts) === 1) {
            return;
        }

        $response->setContent(implode($this->replacementString, $parts));
    }

    public function replaceInCachedResponse(Response $response): void
    {
        $content = $response->getContent();

        if (! $content) {
            return;
        }

        $parts = explode($this->replacementString, $content);

        if (count($parts) === 1) {
            return;
        }

        $csrfToken = csrf_token();

        if (! $csrfToken) {
            return;
        }

        $response->setContent(implode($csrfToken, $parts));
    }
}
