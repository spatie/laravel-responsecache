---
title: Customizing the serializer
weight: 3
---

A serializer is responsible for serializing a response so it can be stored in the cache, and for rebuilding the response from the cache.

The default `JsonSerializer` will work in most cases. It serializes responses as JSON, including the status code, headers, and content.

## Creating a custom serializer

If you have special serialization needs, create a class that implements the `Serializer` interface:

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

Register your custom serializer in the config file:

```php
// config/responsecache.php

'serializer' => \App\Serializers\CustomSerializer::class,
```
