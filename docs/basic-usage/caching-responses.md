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

Cookies belong to a visitor, not to a cached page. For example, a page should not send one visitor's cart cookie to everyone else who opens it.

`CacheResponse` and `FlexibleCacheResponse` leave cookies out of the cached response. They also remove cookies from pages cached before this fix. The visitor whose request first caches the page still receives any cookies set by the controller.

On later requests, the package serves the cached page without running the controller. If you need to set a cookie on every request, set it in a separate middleware and register it before the response cache middleware. You can also choose not to cache that route.

For example, this middleware updates a `last_visit` cookie on every request, even when the page comes from the cache:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLastVisitCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->setCookie(cookie('last_visit', now()->toIso8601String(), 60));

        return $response;
    }
}
```

Register it before `CacheResponse`:

```php
use App\Http\Middleware\SetLastVisitCookie;
use Spatie\ResponseCache\Middlewares\CacheResponse;

Route::get('/posts', [PostController::class, 'index'])
    ->middleware([SetLastVisitCookie::class, CacheResponse::for(minutes(10))]);
```

The cookie is added after `$next($request)` returns. This means the middleware adds it to both newly rendered and cached pages. If `CacheResponse` is already in your `web` middleware group, register `SetLastVisitCookie` before it in that group instead of adding `CacheResponse` to the route again.

With Laravel's usual `web` middleware setup, session and CSRF cookies continue to work. Middleware and replacers can still add cookies for the current visitor.

These rules apply when using the middleware. If you call the response cache API directly to store a response, its cookies are stored too.

For example, this direct call stores the `last_visit` cookie along with the page:

```php
use Spatie\ResponseCache\Facades\ResponseCache;

$response = response('Hello')->cookie('last_visit', now()->toIso8601String(), 60);

ResponseCache::cacheResponse(request(), $response);
```

To leave cookies out when using this API, remove them from the copy you store. Keep the original response so the current visitor still receives their cookie:

```php
$responseToCache = clone $response;
$responseToCache->headers->remove('Set-Cookie');

ResponseCache::cacheResponse(request(), $responseToCache);

return $response;
```

## ETags and replacers

An `ETag` tells the browser which version of a page it has. The browser can send it back on the next request to ask whether it needs to download the page again.

Replacers can change a cached page before sending it to a visitor. For example, the CSRF replacer inserts the current visitor's token. The old ETag may no longer match that page, so the middleware removes it whenever a replacer changes the page's content. This also works for pages cached before the fix. If the content stays the same, the ETag stays too.

If you want to use ETags, register Laravel's `cache.headers:etag` middleware before the response cache middleware:

```php
Route::get('/posts', [PostController::class, 'index'])
    ->middleware(['cache.headers:etag', CacheResponse::for(minutes(10))]);
```

Laravel processes the response in the reverse middleware order. Listing `cache.headers:etag` first lets it calculate the ETag after the package has finished updating the page.

If `CacheResponse` is already in your `web` middleware group, add `cache.headers:etag` before it in that group instead. You don't need to add `CacheResponse` to the route again.

Do not add `public` or `max_age` to pages containing CSRF tokens or other visitor-specific content. These settings can let browsers or proxies reuse a page without asking your application for an updated one.
