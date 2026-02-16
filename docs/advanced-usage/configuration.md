---
title: Configuration
weight: 5
---

## Cache store

By default, the `file` cache driver is used. You can change this to any cache store configured in `config/cache.php`:

```env
RESPONSE_CACHE_DRIVER=redis
```

If you use a cache driver that supports tags (like Redis or Memcached), you'll be able to use [cache tags](/docs/laravel-responsecache/v8/basic-usage/using-tags) for more granular cache clearing.

## Cache lifetime

The default cache lifetime is one week (604800 seconds). You can change it via the environment variable:

```env
RESPONSE_CACHE_LIFETIME=3600
```

## Disabling the cache

You can disable response caching entirely:

```env
RESPONSE_CACHE_ENABLED=false
```

## Ignored query parameters

By default, common tracking parameters like `utm_source`, `gclid`, and `fbclid` are stripped from the cache key. This means that `https://example.com/page` and `https://example.com/page?utm_source=google&gclid=abc` will share the same cached response.

You can customize the list of ignored parameters in the config file.

```php
// config/responsecache.php

'ignored_query_parameters' => [
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_term',
    'utm_content',
    'gclid',
    'fbclid',
],
```

Set this to an empty array if you want all query parameters to be included in the cache key.

## Debug headers

When `APP_DEBUG` is `true`, the package adds a header to cached responses showing when the response was cached. You can customize this behavior.

```php
// config/responsecache.php

'debug' => [
    'add_time_header' => env('APP_DEBUG', false),
    'time_header_name' => 'laravel-responsecache',
    'add_age_header' => env('RESPONSE_CACHE_AGE_HEADER', false),
    'age_header_name' => 'laravel-responsecache-age',
],
```

When `add_age_header` is enabled (requires `add_time_header` to also be active), cached responses will include a header showing how many seconds ago the response was cached.
