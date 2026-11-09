---
title: Customizing the serializer
weight: 3
---

A serializer is responsible for serializing a response so it can be stored in the cache, and for rebuilding the response from the cache.

The default `JsonSerializer` will work in most cases. It serializes responses as JSON, including the status code, headers, and content.

## Faster cache hits with the `RawBodySerializer`

Because the `JsonSerializer` stores the body as a JSON string, every cache hit has to decode the whole page again. The `RawBodySerializer` stores the status code and headers as a small JSON header, followed by the raw body. Entries are smaller and much faster to read, and it can also store pages that aren't valid UTF-8.

```php
// config/responsecache.php

'serializer' => \Spatie\ResponseCache\Serializers\RawBodySerializer::class,
```

It can read entries written by the `JsonSerializer`, so you don't need to clear the cache when switching to it. Clear the cache when you switch back, because the `JsonSerializer` can't read its entries.

## Creating a custom serializer

If you have special serialization needs, you can create a class that implements the `Serializer` interface.

```php
namespace App\Serializers;

use Spatie\ResponseCache\Serializers\Serializer;
use Symfony\Component\HttpFoundation\Response;

class CustomSerializer implements Serializer
{
    public function serialize(Response $response): string
    {
        // Your serialization logic
    }

    public function unserialize(string $serializedResponse): Response
    {
        // Your unserialization logic
    }
}
```

Then register your custom serializer in the config file.

```php
// config/responsecache.php

'serializer' => \App\Serializers\CustomSerializer::class,
```
