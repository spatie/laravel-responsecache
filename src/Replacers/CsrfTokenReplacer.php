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

        if (! str_contains($content, $csrfToken)) {
            return;
        }

        $response->setContent(str_replace(
            $csrfToken,
            $this->replacementString,
            $content,
        ));
    }

    public function replaceInCachedResponse(Response $response): void
    {
        $content = $response->getContent();

        if (! $content || ! str_contains($content, $this->replacementString)) {
            return;
        }

        $csrfToken = csrf_token();

        if (! $csrfToken) {
            return;
        }

        $response->setContent(str_replace(
            $this->replacementString,
            $csrfToken,
            $content,
        ));
    }
}
