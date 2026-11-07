# Upgrading

Because there are many breaking changes an upgrade is not that easy. There are many edge cases this guide does not cover. We accept PRs to improve this guide.

## 9.0.0

Laravel ResponseCache v9 makes serving cached responses considerably faster. Most applications can upgrade without code changes.

### New default serializer

The default serializer is now `RawBodySerializer`. It stores the status code and headers as a small JSON header, followed by the raw response body. Before, the whole response was JSON-encoded, so every cache hit had to decode the escaped body.

You don't need to clear your cache when upgrading, because `RawBodySerializer` can still read entries written by `JsonSerializer`. If you downgrade to v8 afterwards, clear your cache, because v8 can't read the new format.

If you published the config file, update the serializer to get the faster format:

```php
// config/responsecache.php

'serializer' => \Spatie\ResponseCache\Serializers\RawBodySerializer::class,
```

`JsonSerializer` is still available if you need every cache entry to be valid JSON.

### Cookies are no longer stored in cached responses

`Set-Cookie` headers are removed from responses before they're cached. Before, a cached response replayed the cookies of the visitor who triggered the cache miss, such as their session cookie or a cookie your controller set, to every visitor who got that cached page.

The visitor who triggers the cache miss still receives their cookies. Cached hits only carry the cookies that your middleware adds for the current visitor, such as the session and `XSRF-TOKEN` cookies in the `web` group.

If a page sets a cookie that every visitor needs, set it in a middleware that runs outside the response cache middleware, or exclude that route from caching.

### Stale ETags are removed from cached responses

When a replacer changes a response before it's cached, for example by swapping the CSRF token for a placeholder, a stored `ETag` header is removed. That `ETag` described the original body, not the page later visitors receive. Responses that no replacer changed keep their `ETag`.

### `getCachedResponseFor()` accepts a cache key

`ResponseCache::getCachedResponseFor()` has a new optional `$cacheKey` parameter, so the middleware only hashes a request once per cache hit. If you extend `ResponseCache` and override this method, update its signature:

```php
public function getCachedResponseFor(Request $request, array $tags = [], ?string $cacheKey = null): ?Response
```

The middleware now gets cache keys from the new `ResponseCache::cacheKeyFor()` method, which uses the hasher that `ResponseCache` was built with, instead of resolving `RequestHasher` from the container on every request.

### Middleware and replacers are resolved once

`FlexibleCacheResponse` is now registered as a singleton, like `CacheResponse` already was. Replacers are resolved once and reused, until the `responsecache.replacers` config changes.

Your cache profile, hasher, serializer and replacers may therefore be reused across requests. Don't store request state in their constructors (for example by injecting the `Request` or the authenticated user). Use the `$request` passed to their methods, or resolve request state inside those methods, as the built-in classes do.

## 8.0.0

Laravel ResponseCache v8 modernizes the package for PHP 8.4+ and Laravel 12+.

### Middleware API

The old string-based middleware syntax has been replaced with a fluent `::for()` method using named parameters.

```php
// Before
Route::get('/posts')->middleware('cacheResponse:300,posts,api');

// After
Route::get('/posts')->middleware(CacheResponse::for(
    lifetime: CarbonInterval::minutes(5),
    tags: ['posts', 'api'],
));
```

You can also use attributes directly on controller methods:

```php
#[Cache(lifetime: 5 * 60, tags: ['posts', 'api'])]
public function index() {}

#[NoCache]
public function store() {}
```

### Flexible caching (stale-while-revalidate)

A new `FlexibleCacheResponse` middleware uses Laravel's `Cache::flexible()` to serve stale content instantly while refreshing in the background.

```php
Route::get('/dashboard')->middleware(FlexibleCacheResponse::for(
    lifetime: CarbonInterval::hours(1),
    grace: CarbonInterval::minutes(5),
));

// Or using an attribute
#[FlexibleCache(lifetime: 60 * 60, grace: 5 * 60)]
public function dashboard() {}
```

### Config structure

The config file has been reorganized from flat keys into logical groups.

```php
// Before
'cache_lifetime_in_seconds' => 604800,
'cache_store' => 'file',
'add_cache_time_header' => false,
'cache_bypass_header' => ['name' => null, 'value' => null],

// After
'cache' => [
    'lifetime_in_seconds' => 604800,
    'store' => 'file',
    'tag' => '',
],
'debug' => [
    'enabled' => false,
    'cache_time_header_name' => 'X-Cache-Time',
    'cache_status_header_name' => 'X-Cache-Status',
    'cache_age_header_name' => 'X-Cache-Age',
    'cache_key_header_name' => 'X-Cache-Key',
],
'bypass' => [
    'header_name' => null,
    'header_value' => null,
],
```

Update your code accordingly:
- `config('responsecache.cache_lifetime_in_seconds')` → `config('responsecache.cache.lifetime_in_seconds')`
- `config('responsecache.cache_store')` → `config('responsecache.cache.store')`
- `config('responsecache.add_cache_time_header')` → `config('responsecache.debug.enabled')`
- `config('responsecache.cache_bypass_header.name')` → `config('responsecache.bypass.header_name')`

### Default serializer changed to JSON

The default serializer is now `JsonSerializer` which uses JSON encoding instead of PHP `serialize()`. The old `DefaultSerializer` has been removed. If you had a custom serializer, implement the `Serializer` interface directly.

You must clear your cache after upgrading since the serialization format has changed.

### Event classes renamed

All event classes now have an `Event` suffix:

- `CacheMissed` → `CacheMissedEvent`
- `ResponseCacheHit` → `ResponseCacheHitEvent`
- `ClearingResponseCache` → `ClearingResponseCacheEvent`
- `ClearedResponseCache` → `ClearedResponseCacheEvent`
- `ClearingResponseCacheFailed` → `ClearingResponseCacheFailedEvent`

### Ignored query parameters

A new `ignored_query_parameters` config option strips tracking parameters (like `utm_source`, `gclid`, `fbclid`) from cache keys. This prevents duplicate cache entries for the same page with different tracking parameters.

### Enums

If you've extended the package, note that HTTP methods now use the `HttpMethod` enum and response types use the `ResponseType` enum internally.

### Migration steps

1. Update your middleware calls to use `::for()` or switch to attributes
2. Republish and merge the config file: `php artisan vendor:publish --tag="responsecache-config" --force`
3. Clear the cache: `php artisan responsecache:clear`
4. Update any event class references to use the new `Event` suffix
5. Update any config references in your code

## 6.0.0

If you're using the default settings you can upgrade without any problems.

- By default the `CsrfTokenReplacer` will be applied before caching the request. For most users, this will be harmless
- The `flush` method has been removed, use `clear` instead
- The `Flush` command has been removed. Use `ClearCommand` instead


## 5.0.0

As of Laravel 5.8 defining cache time in seconds is supported. All mentions of response cache time should be changed from minutes to seconds:

- If you've published to config file, change `cache_lifetime_in_minutes` to `cache_lifetime_in_seconds`
- If you're using the `cacheResponse` middleware, change the optional time parameter to seconds (value * 60)
- If you're extending `CacheResponse`, `ResponseCacheRepository`, `BaseCacheProfile` or `CacheResponse` you should check the relevant methods
