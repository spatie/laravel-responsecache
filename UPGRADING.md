# Upgrading

Because there are many breaking changes an upgrade is not that easy. There are many edge cases this guide does not cover. We accept PRs to improve this guide.

## 8.0.0

### Summary of Changes

Laravel ResponseCache v8 modernizes the package with PHP 8.5+ features and an improved developer experience:

**New Features:**
- Enum-based type safety for HTTP methods and response types
- Attribute-based cache configuration using `#[Cache]` and `#[FlexibleCache]`
- Restructured config with logical grouping (cache.*, debug.*, bypass.*)
- JSON serialization by default (replacing PHP serialize() for security)
- Enhanced fluent API with named parameters
- Better debug headers for troubleshooting

**Breaking Changes:**
1. Middleware API completely redesigned
2. Config structure reorganized with nesting
3. Default serializer changed to JSON
4. Enum usage internally (affects custom implementations)

### Breaking Changes Detail

#### 1. Middleware API Changes

**Old string-based syntax (removed):**
```php
Route::get('/posts')->middleware('cacheResponse:300,posts,api');
Route::get('/live')->middleware(FlexibleCacheResponse::flexible(180, 900));
```

**New fluent syntax:**
```php
Route::get('/posts')->middleware(CacheResponse::for(
    lifetime: 300,
    tags: ['posts', 'api']
));

Route::get('/live')->middleware(FlexibleCacheResponse::for(
    fresh: CarbonInterval::minutes(3),
    stale: CarbonInterval::minutes(15),
    defer: true
));
```

**Or use attributes (recommended):**
```php
#[Cache(lifetime: 300, tags: ['posts', 'api'])]
public function index() {}

#[FlexibleCache(fresh: 180, stale: 900, defer: true)]
public function show($id) {}

#[NoCache]
public function store() {}
```

#### 2. Config Structure Changes

**Old flat structure:**
```php
'cache_lifetime_in_seconds' => 604800,
'add_cache_time_header' => false,
'cache_store' => 'file',
'cache_bypass_header' => [...],
```

**New nested structure:**
```php
'cache' => [
    'lifetime' => 604800,
    'store' => 'file',
    'tag' => '',
],
'debug' => [
    'add_time_header' => false,
    'time_header_name' => 'laravel-responsecache',
],
'bypass' => [
    'enabled' => false,
    'header_name' => null,
    'header_value' => null,
],
```

**Update your code:**
- `config('responsecache.cache_lifetime_in_seconds')` → `config('responsecache.cache.lifetime_in_seconds')`
- `config('responsecache.cache_store')` → `config('responsecache.cache.store')`
- `config('responsecache.add_cache_time_header')` → `config('responsecache.debug.add_time_header')`
- `config('responsecache.cache_bypass_header.name')` → `config('responsecache.bypass.header_name')`

#### 3. Default Serializer Changed

**Old:** `DefaultSerializer` using PHP `serialize()` (security risk)
**New:** `JsonSerializer` using JSON encoding (more secure)

The `DefaultSerializer` class has been removed. If you had customized the serializer, implement the `Serializer` interface directly.

#### 4. Enum Usage (Internal)

If you've extended the package:
- HTTP methods now use `HttpMethod` enum with PascalCase: `HttpMethod::Get`, `HttpMethod::Post`
- Response types use `ResponseType` enum: `ResponseType::Normal`, `ResponseType::File`
- Update custom cache profiles to use enums instead of strings

### Migration Steps

1. **Update middleware in routes:**
   ```bash
   # Search for: 'cacheResponse:
   # Replace with: CacheResponse::for(lifetime:
   ```

2. **Update config file:**
   ```bash
   php artisan vendor:publish --tag="responsecache-config" --force
   ```
   Then merge your custom settings into the new structure.

3. **Clear cache:**
   ```bash
   php artisan responsecache:clear
   ```
   This is necessary because the serializer format changed.

4. **Update tests:**
   Update any tests that reference config keys or middleware syntax.

5. **Consider using attributes:**
   Migrate your most common cache configurations to controller attributes for better readability.

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
