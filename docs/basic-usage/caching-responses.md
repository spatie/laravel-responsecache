---
title: Caching responses
weight: 1
---

After [installing the package](/docs/laravel-responsecache/v8/installation-setup), the `CacheResponse` middleware will cache all successful GET requests that return text based content (such as HTML and JSON) for a week. Logged in users will each have their own separate cache.

## Using middleware

You can configure caching per route using the `CacheResponse::for()` method:

```php
use Spatie\ResponseCache\Middlewares\CacheResponse;

// Group routes with the same cache configuration
Route::middleware(CacheResponse::for(minutes(10)))->group(function () {
    Route::get('/about', [PageController::class, 'about']);
    Route::get('/contact', [PageController::class, 'contact']);
});

// Cache for 5 minutes
Route::get('/posts', [PostController::class, 'index'])
    ->middleware(CacheResponse::for(minutes(5)));

// Cache for 1 hour with tags
Route::get('/posts/{post}', [PostController::class, 'show'])
    ->middleware(CacheResponse::for(hours(1), tags: ['posts']));

// Cache with a specific driver
Route::get('/api/posts', [ApiPostController::class, 'index'])
    ->middleware(CacheResponse::for(minutes(5), tags: ['api', 'posts'], driver: 'redis'));
```

The `lifetime` parameter accepts Laravel's `minutes()`, `hours()`, and `days()` helpers (or any `CarbonInterval`), or an `int` in seconds.

## Using attributes

You can also configure caching with the `#[Cache]` attribute on your controller methods:

```php
use Spatie\ResponseCache\Attributes\Cache;

class PostController
{
    #[Cache(lifetime: 300, tags: ['posts'])]
    public function index()
    {
        return view('posts.index', ['posts' => Post::all()]);
    }

    #[Cache(lifetime: 600, driver: 'redis')]
    public function show(Post $post)
    {
        return view('posts.show', ['post' => $post]);
    }
}
```

The `#[Cache]` attribute accepts:
- `lifetime`: Cache duration in seconds (defaults to the config value)
- `tags`: Cache tags (array)
- `driver`: Cache driver to use (defaults to the config value)

Attributes can also be applied at the class level to cache all methods in the controller:

```php
use Spatie\ResponseCache\Attributes\Cache;

#[Cache(lifetime: 300, tags: ['posts'])]
class PostController
{
    public function index() { /* ... */ }
    public function show(Post $post) { /* ... */ }
}
```
