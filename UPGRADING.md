# Upgrading

Because there are many breaking changes an upgrade is not that easy. There are many edge cases this guide does not cover. We accept PRs to improve this guide.

## 8.6.2

This update fixes how cached pages handle cookies and ETags. You don't need to change your config or clear your response cache. The cache format and package API stay the same.

Check these two things in your application:

1. **Cookies set by controllers.** The middleware no longer saves cookies with a cached page or sends those cookies to later visitors. The first visitor still receives their cookies. If a cookie needs to be set on every request, move that code to middleware registered before the response cache middleware, or stop caching that route. Laravel's usual session and CSRF cookies continue to work.
2. **ETags on cached pages.** An ETag tells the browser which version of a page it has. If a replacer changes the page, for example to insert a CSRF token, the middleware now removes the old ETag. If you use ETags, register `cache.headers:etag` before the response cache middleware so Laravel can calculate one for the updated page.

The updated middleware also handles pages that are already in the cache. If your application runs on several servers, update all of them. Servers still running the older version keep the old behavior until they are updated.

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
