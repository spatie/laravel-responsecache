---
title: Creating a replacer
weight: 2
---

Replacers allow you to swap dynamic content with placeholders before caching, and replace the placeholders with fresh values when serving the cached response. This is useful for content that changes between requests, like CSRF tokens.

The package ships with a `CsrfTokenReplacer` that handles CSRF tokens automatically.

## Creating a custom replacer

Implement the `Replacer` interface:

```php
namespace App\Replacers;

use Spatie\ResponseCache\Replacers\Replacer;
use Symfony\Component\HttpFoundation\Response;

class UserNameReplacer implements Replacer
{
    protected string $placeholder = '<username-placeholder>';

    public function prepareResponseToCache(Response $response): void
    {
        $content = $response->getContent();

        if (! $content) {
            return;
        }

        $userName = auth()->user()?->name ?? 'Guest';

        $response->setContent(str_replace(
            $userName,
            $this->placeholder,
            $content,
        ));
    }

    public function replaceInCachedResponse(Response $response): void
    {
        $content = $response->getContent();

        if (! $content || ! str_contains($content, $this->placeholder)) {
            return;
        }

        $userName = auth()->user()?->name ?? 'Guest';

        $response->setContent(str_replace(
            $this->placeholder,
            $userName,
            $content,
        ));
    }
}
```

Register your replacer in the config file:

```php
// config/responsecache.php

'replacers' => [
    \Spatie\ResponseCache\Replacers\CsrfTokenReplacer::class,
    \App\Replacers\UserNameReplacer::class,
],
```
