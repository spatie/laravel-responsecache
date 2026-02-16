---
title: Flexible caching (SWR)
weight: 2
---

Sometimes you want fast responses without serving data that's too outdated. Flexible caching uses the stale-while-revalidate (SWR) pattern to achieve this: it serves a cached response instantly, while quietly refreshing the cache in the background.

Here's an example:

```php
Route::get('/api/posts', [PostController::class, 'index'])
    ->middleware(FlexibleCacheResponse::for(lifetime: hours(1), grace: minutes(5)));
```

This is what happens with the configuration above.

- **0–60 minutes** (`lifetime`): The cached response is served directly. No regeneration happens.
- **60–65 minutes** (`grace`): The cached response is still served instantly, but a background refresh is triggered so the next request gets fresh data.
- **After 65 minutes**: The cache has fully expired. The next request will wait for a fresh response, which is then cached and the cycle starts over.

<img src="/docs/laravel-responsecache/v8/images/flexible-cache-timeline.svg">

The `lifetime` parameter defines how long a cached response is considered up-to-date. The `grace` parameter defines how much additional time the old response can still be served while a new one is being generated in the background.

During the grace period, two things happen simultaneously: the stale response is sent to the browser instantly, while the server regenerates a fresh response in the background using Laravel's `defer`.

<img src="/docs/laravel-responsecache/v8/images/grace-period.svg">

Under the hood, this package uses Laravel's `Cache::flexible()` method. Laravel's docs refer to these concepts as "fresh" and "stale" — our `lifetime` maps to "fresh" and `grace` maps to "stale". You can find more info in the [Laravel documentation](https://laravel.com/docs/12.x/cache#swr).

## Using middleware

You can configure flexible caching per route using the `FlexibleCacheResponse::for()` method.

```php
use Spatie\ResponseCache\Middlewares\FlexibleCacheResponse;

Route::get('/api/posts', [PostController::class, 'index'])
    ->middleware(FlexibleCacheResponse::for(lifetime: hours(1), grace: minutes(5)));

// With cache tags
Route::get('/api/stats', [StatsController::class, 'index'])
    ->middleware(FlexibleCacheResponse::for(
        lifetime: minutes(5),
        grace: hours(1),
        tags: ['stats', 'api'],
    ));

// Group routes with the same flexible cache configuration
Route::middleware(FlexibleCacheResponse::for(lifetime: minutes(1), grace: minutes(5)))->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/stats', [StatsController::class, 'index']);
});
```

The `lifetime` and `grace` parameters accept Laravel's `minutes()`, `hours()`, and `seconds()` helpers (or any `CarbonInterval`), or an `int` in seconds.

## Using attributes

You can also configure flexible caching with the `#[FlexibleCache]` attribute.

```php
use Spatie\ResponseCache\Attributes\FlexibleCache;

class PostController
{
    #[FlexibleCache(lifetime: 60 * 60, grace: 5 * 60)]
    public function index()
    {
        return view('posts.index', ['posts' => Post::all()]);
    }

    #[FlexibleCache(lifetime: 60 * 60, grace: 5 * 60, tags: ['posts', 'api'])]
    public function apiIndex()
    {
        return response()->json(Post::all());
    }
}
```

The attribute can also be applied at the class level to apply to all methods.

```php
use Spatie\ResponseCache\Attributes\FlexibleCache;

#[FlexibleCache(lifetime: 60 * 60, grace: 5 * 60)]
class DashboardController
{
    public function index() { /* ... */ }
    public function stats() { /* ... */ }
}
```

The `#[FlexibleCache]` attribute accepts the following parameters.
- `lifetime`: How long the cache is considered up-to-date (in seconds)
- `grace`: How long to serve the old response while refreshing in the background (in seconds)
- `tags`: Optional cache tags (array)
