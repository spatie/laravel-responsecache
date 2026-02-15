---
title: Events
weight: 6
---

There are several events you can use to monitor and debug response caching in your application.

## ResponseCacheHit

`Spatie\ResponseCache\Events\ResponseCacheHit`

Fired when a cached response is found and returned. The event receives the `Request` object, the cache age in seconds, and the tags used.

## CacheMissed

`Spatie\ResponseCache\Events\CacheMissed`

Fired when no cached response is found for the request. The event receives the `Request` object.

## ClearingResponseCache

`Spatie\ResponseCache\Events\ClearingResponseCache`

Fired when the response cache is about to be cleared.

## ClearedResponseCache

`Spatie\ResponseCache\Events\ClearedResponseCache`

Fired after the response cache has been successfully cleared.

## ClearingResponseCacheFailed

`Spatie\ResponseCache\Events\ClearingResponseCacheFailed`

Fired when clearing the response cache fails.
