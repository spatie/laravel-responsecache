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

## Minimum requests before caching

By default, every cacheable response is stored on its first request. When a large share of your traffic consists of URLs that are requested only once, such as crawlers walking through a catalog, those responses fill the cache without ever being served from it.

You can require a page to be requested a number of times before its response is stored. This works like the `proxy_cache_min_uses` directive in nginx.

```env
RESPONSE_CACHE_MINIMUM_REQUESTS=2
```

Requests are counted per cache key. A request only counts when its response could have been cached, so an error response does not bring a page closer to the minimum.

The counters expire after one day by default. A page must reach the minimum within that window, otherwise the count starts over.

```env
RESPONSE_CACHE_MINIMUM_REQUESTS_LIFETIME=86400
```

The counters live on the response cache store. When that store evicts entries under memory pressure, the counters are usually the first to go and a page may never reach the minimum. You can keep them on a separate store:

```env
RESPONSE_CACHE_MINIMUM_REQUESTS_STORE=redis
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
