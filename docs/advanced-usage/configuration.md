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

When `APP_DEBUG` is `true`, the package adds debug headers to cached responses. You can customize this behavior.

```php
// config/responsecache.php

'debug' => [
    'enabled' => env('APP_DEBUG', false),
    'cache_time_header_name' => 'X-Cache-Time',
    'cache_status_header_name' => 'X-Cache-Status',
    'cache_age_header_name' => 'X-Cache-Age',
    'cache_key_header_name' => 'X-Cache-Key',
],
```

When enabled, cached responses will include the following headers:
- `X-Cache-Status`: `HIT` or `MISS` indicating whether the response was served from cache
- `X-Cache-Time`: the timestamp when the response was cached
- `X-Cache-Age`: how many seconds ago the response was cached (only on cache hits)
- `X-Cache-Key`: the cache key used (only when `app.debug` is `true`)
