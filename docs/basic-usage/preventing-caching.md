---
title: Preventing caching
weight: 3
---

## Using the DoNotCacheResponse middleware

You can prevent caching for specific routes using the `doNotCacheResponse` middleware.

```php
Route::get('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('doNotCacheResponse');
```

## Using the NoCache attribute

You can also disable caching for specific controller methods using the `#[NoCache]` attribute.

```php
use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\NoCache;

#[Cache(lifetime: 5 * 60)]
class PostController
{
    public function index()
    {
        // This will be cached (inherits class-level attribute)
    }

    #[NoCache]
    public function store()
    {
        // This will not be cached
    }
}
```

## Bypassing the cache

You can securely bypass the cache to always receive a fresh response. This is useful for profiling or debugging.

To enable this, set the `CACHE_BYPASS_HEADER_NAME` and `CACHE_BYPASS_HEADER_VALUE` environment variables.

```env
CACHE_BYPASS_HEADER_NAME=X-Cache-Bypass
CACHE_BYPASS_HEADER_VALUE=your-secret-value
```

Then include that header when making requests to bypass the cache.
