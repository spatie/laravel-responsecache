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

        $csrf_token = csrf_token();

        if (! $csrf_token) {
            return;
        }

        // Skip if CSRF token not in content
        if (! str_contains($content, $csrf_token)) {
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
        $content = $response->getContent();

        if (! $content || ! str_contains($content, $this->replacementString)) {
            return;
        }

        $csrf_token = csrf_token();

        if (! $csrf_token) {
            return;
        }

        $response->setContent(str_replace(
            $this->replacementString,
            $csrf_token,
            $content,
        ));
    }
}
