---
title: Customizing the serializer
weight: 3
---

A serializer is responsible for serializing a response so it can be stored in the cache, and for rebuilding the response from the cache.

The default `RawBodySerializer` will work in most cases. It stores the status code and headers as a small JSON header, followed by the raw response body. Because the body isn't escaped, entries are smaller and much faster to read than a fully JSON-encoded response. It can also store pages that aren't valid UTF-8, which `JsonSerializer` can't, and it can read entries written by `JsonSerializer`.

If you need every cache entry to be valid JSON, for example because other tools read the cache directly, you can switch to the `JsonSerializer`.

```php
// config/responsecache.php

'serializer' => \Spatie\ResponseCache\Serializers\JsonSerializer::class,
```

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

The package may reuse one instance of your serializer for several requests, so don't keep per-request state in it.
