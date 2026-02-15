---
title: Custom cache profiles
weight: 1
---

A cache profile determines which requests should be cached, for how long, and how to differentiate between users. The default profile `CacheAllSuccessfulGetRequests` caches all successful GET requests (excluding AJAX) that return text based content.

You can create your own cache profile by implementing the `CacheProfile` interface:

```php
namespace App\CacheProfiles;

use DateTime;
use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Symfony\Component\HttpFoundation\Response;

class CustomCacheProfile implements CacheProfile
{
    public function enabled(Request $request): bool
    {
        return config('responsecache.enabled');
    }

    public function shouldCacheRequest(Request $request): bool
    {
        // Only cache GET and HEAD requests
        return $request->isMethod('GET') || $request->isMethod('HEAD');
    }

    public function shouldCacheResponse(Response $response): bool
    {
        return $response->isSuccessful();
    }

    public function cacheRequestUntil(Request $request): DateTime
    {
        return now()->addMinutes(30);
    }

    public function useCacheNameSuffix(Request $request): string
    {
        // Return a unique string per user (or empty for shared cache)
        return auth()->user()?->id ?? '';
    }
}
```

Register your custom profile in the config file:

```php
// config/responsecache.php

'cache_profile' => App\CacheProfiles\CustomCacheProfile::class,
```

The `useCacheNameSuffix` method is used to differentiate cache entries between users. By default, it returns the authenticated user's ID, meaning each logged-in user gets their own cache. Return an empty string if you want all users to share the same cache.
