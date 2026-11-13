---
title: Customizing the serializer
weight: 3
---

A serializer is responsible for serializing a response so it can be stored in the cache, and for rebuilding the response from the cache.

The default `JsonSerializer` will work in most cases. It serializes responses as JSON, including the status code, headers, and content.

## Faster cache hits with the `RawBodySerializer`

The `JsonSerializer` decodes the entire response body from JSON on every cache hit. The opt-in `RawBodySerializer` stores the status code and headers as JSON, followed by a newline and the unescaped body. This avoids JSON decoding of the body and can reduce cache-hit time, especially for larger pages. It can also store bodies that aren't valid UTF-8.

```php
// config/responsecache.php

'serializer' => \Spatie\ResponseCache\Serializers\RawBodySerializer::class,
```

It can read entries written by the `JsonSerializer`, so you don't need to clear the cache when switching to it. Clear the response cache when switching back, because the `JsonSerializer` can't read its entries. During rolling deployments, keep all processes that share the response cache on the same serializer: an older process using `JsonSerializer` can't read entries written by a process using `RawBodySerializer`.

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
