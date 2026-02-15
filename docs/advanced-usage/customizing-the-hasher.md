---
title: Customizing the hasher
weight: 4
---

The hasher generates a unique cache key for each request. The default `DefaultHasher` creates a hash based on the request's host, URI, method, and the cache name suffix from the cache profile.

## Creating a custom hasher

If you need a different hashing strategy, create a class that implements the `RequestHasher` interface:

```php
namespace App\Hashers;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Hasher\RequestHasher;

class CustomHasher implements RequestHasher
{
    public function getHashFor(Request $request): string
    {
        // Your hashing logic
        return hash('xxh128', $request->fullUrl());
    }
}
```

Register your custom hasher in the config file:

```php
// config/responsecache.php

'hasher' => \App\Hashers\CustomHasher::class,
```
