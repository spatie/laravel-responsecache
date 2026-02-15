---
title: Clearing the cache
weight: 4
---

## Clearing the entire cache

The entire response cache can be cleared programmatically:

```php
use Spatie\ResponseCache\Facades\ResponseCache;

ResponseCache::clear();
```

Or using the artisan command:

```bash
php artisan responsecache:clear
```

## Forgetting specific URIs

You can forget specific URIs:

```php
use Spatie\ResponseCache\Facades\ResponseCache;

// Forget one URI
ResponseCache::forget('/some-uri');

// Forget several URIs
ResponseCache::forget(['/some-uri', '/other-uri']);

// Or pass them as separate arguments
ResponseCache::forget('/some-uri', '/other-uri');
```

You can also forget a specific URI using the artisan command:

```bash
php artisan responsecache:clear --url=/some-uri
```

## Forgetting a selection of cached items

For more control, use `selectCachedItems()` to specify exactly which cached items should be forgotten:

```php
use Spatie\ResponseCache\Facades\ResponseCache;

// Forget all PUT responses for a URI
ResponseCache::selectCachedItems()
    ->withPutMethod()
    ->forUrls('/some-uri')
    ->forget();

// Forget multiple URIs
ResponseCache::selectCachedItems()
    ->forUrls('/some-uri', '/other-uri')
    ->forget();

// Forget with a specific cache name suffix (by default the suffix is the user ID or an empty string)
ResponseCache::selectCachedItems()
    ->usingSuffix('100')
    ->forUrls('/some-uri')
    ->forget();

// All options combined
ResponseCache::selectCachedItems()
    ->withPutMethod()
    ->withHeaders(['foo' => 'bar'])
    ->withCookies(['cookie1' => 'value'])
    ->withParameters(['param1' => 'value'])
    ->withRemoteAddress('127.0.0.1')
    ->usingSuffix('100')
    ->usingTags('tag1', 'tag2')
    ->forUrls('/some-uri', '/other-uri')
    ->forget();
```

## Using model events

You can clear the cache whenever a model changes by using model events:

```php
namespace App\Traits;

use Spatie\ResponseCache\Facades\ResponseCache;

trait ClearsResponseCache
{
    public static function bootClearsResponseCache(): void
    {
        self::created(fn () => ResponseCache::clear());
        self::updated(fn () => ResponseCache::clear());
        self::deleted(fn () => ResponseCache::clear());
    }
}
```

## Clearing tagged content

If you're using cache tags, you can clear only responses with specific tags:

```php
use Spatie\ResponseCache\Facades\ResponseCache;

// Clear only responses tagged with 'posts'
ResponseCache::clear(['posts']);

// Clear responses tagged with both 'foo' and 'bar'
ResponseCache::clear(['foo', 'bar']);
```
