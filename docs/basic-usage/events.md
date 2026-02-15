---
title: Events
weight: 6
---

There are several events you can use to monitor and debug response caching in your application.

## ResponseCacheHitEvent

`Spatie\ResponseCache\Events\ResponseCacheHitEvent`

Fired when a cached response is found and returned. The event receives the `Request` object, the cache age in seconds, and the tags used.

## CacheMissedEvent

`Spatie\ResponseCache\Events\CacheMissedEvent`

Fired when no cached response is found for the request. The event receives the `Request` object.

## ClearingResponseCacheEvent

`Spatie\ResponseCache\Events\ClearingResponseCacheEvent`

Fired when the response cache is about to be cleared.

## ClearedResponseCacheEvent

`Spatie\ResponseCache\Events\ClearedResponseCacheEvent`

Fired after the response cache has been successfully cleared.

## ClearingResponseCacheFailedEvent

`Spatie\ResponseCache\Events\ClearingResponseCacheFailedEvent`

Fired when clearing the response cache fails.
