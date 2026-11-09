---
title: Caching responses
weight: 1
---

After [installing the package](/docs/laravel-responsecache/v8/installation-setup), the `CacheResponse` middleware will cache all successful GET requests that return text based content (such as HTML and JSON) for a week. Logged in users will each have their own separate cache.

## Using middleware

You can configure caching per route using the `CacheResponse::for()` method.

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

```

The `lifetime` parameter accepts Laravel's `minutes()`, `hours()`, and `days()` helpers (or any `CarbonInterval`), or an `int` in seconds.

## Using attributes

You can also configure caching with the `#[Cache]` attribute on your controller methods.

```php
use Spatie\ResponseCache\Attributes\Cache;

class PostController
{
    #[Cache(lifetime: 5 * 60, tags: ['posts'])]
    public function index()
    {
        return view('posts.index', ['posts' => Post::all()]);
    }

    #[Cache(lifetime: 10 * 60)]
    public function show(Post $post)
    {
        return view('posts.show', ['post' => $post]);
    }
}
```

The `#[Cache]` attribute accepts the following parameters.
- `lifetime`: Cache duration in seconds (defaults to the config value)
- `tags`: Cache tags (array)

Attributes can also be applied at the class level to cache all methods in the controller.

```php
use Spatie\ResponseCache\Attributes\Cache;

#[Cache(lifetime: 5 * 60, tags: ['posts'])]
class PostController
{
    public function index() { /* ... */ }
    public function show(Post $post) { /* ... */ }
}
```

Invokable controllers are supported too, whether or not you register the route with an explicit method.

```php
use Spatie\ResponseCache\Attributes\Cache;

class ShowPostsController
{
    #[Cache(lifetime: 5 * 60, tags: ['posts'])]
    public function __invoke()
    {
        return view('posts.index', ['posts' => Post::all()]);
    }
}

Route::get('posts', ShowPostsController::class);
```

## Cookies

`CacheResponse` and `FlexibleCacheResponse` do not store `Set-Cookie` headers. They also remove stored cookies when serving existing entries, so a cached response cannot overwrite the current visitor's cookies with those of an earlier visitor.

The visitor who triggers a cache miss still receives the cookies set by the controller. On cache hits, outer middleware and replacers can add cookies for the current visitor. With Laravel's usual `web` middleware order, session and `XSRF-TOKEN` cookies continue to be set for the current visitor.

If every visitor needs a cookie, set it in middleware that wraps the response cache middleware, or exclude the route from response caching. A controller does not run on cache hits.

This handling belongs to the middleware. Direct calls to the response cache API still serialize the response you provide.

## ETags and replacers

An `ETag` describes a response body. When a replacer changes the body, such as when replacing a CSRF token, the middleware removes the stored `ETag`. This applies when writing entries and when serving existing ones. ETags are preserved when replacers leave the body unchanged.

To generate a validator for the body each visitor receives, place Laravel's `cache.headers:etag` middleware outside the response cache middleware:

```php
Route::get('/posts', [PostController::class, 'index'])
    ->middleware(['cache.headers:etag', CacheResponse::for(minutes(10))]);
```

The order above matters: `cache.headers` must process the response after the replacers run. If `CacheResponse` is already registered in your `web` group, place `cache.headers:etag` before it in that group rather than adding another response cache middleware to the route.

Do not use `public` or `max_age` for pages containing CSRF tokens or other visitor-specific data. Those options can allow a browser or proxy to reuse a page without contacting the application.
