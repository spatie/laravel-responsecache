---
title: Using tags
weight: 5
---

If the [cache driver you configured supports tags](https://laravel.com/docs/12.x/cache#cache-tags), you can tag cached responses. This allows you to clear specific groups of cached responses without clearing the entire cache.

## Tagging with middleware

```php
use Spatie\ResponseCache\Middlewares\CacheResponse;

Route::get('/posts', [PostController::class, 'index'])
    ->middleware(CacheResponse::for(minutes(5), tags: ['posts']));

Route::get('/api/posts', [ApiPostController::class, 'index'])
    ->middleware(CacheResponse::for(minutes(5), tags: ['posts', 'api']));
```

## Tagging with attributes

```php
use Spatie\ResponseCache\Attributes\Cache;

class PostController
{
    #[Cache(lifetime: 5 * 60, tags: ['posts'])]
    public function index() { /* ... */ }

    #[Cache(lifetime: 5 * 60, tags: ['posts', 'api'])]
    public function apiIndex() { /* ... */ }
}
```

## Global tags

You can also set a global tag in the config file. All cached responses will receive this tag.

```php
// config/responsecache.php

'cache' => [
    'tag' => 'responsecache',
    // or multiple tags:
    'tag' => ['responsecache', 'pages'],
],
```

## Clearing by tag

You can clear responses that are assigned a specific tag.

```php
use Spatie\ResponseCache\Facades\ResponseCache;

// Clear only responses tagged with 'posts'
ResponseCache::clear(['posts']);
```

This uses [Laravel's built-in cache tags](https://laravel.com/docs/12.x/cache#cache-tags) functionality.
