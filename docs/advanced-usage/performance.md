---
title: Performance
weight: 6
---

Serving a cached response skips your controller, but Laravel still boots and runs your middleware for every request. Often that takes longer than the response cache itself. These techniques can make cached pages faster still.

## Let browsers reuse pages they already have

Laravel's `cache.headers` middleware can add an `ETag` to responses. When a browser requests the page again with a matching `If-None-Match` header, Laravel answers with `304 Not Modified` instead of sending the whole page.

Add it before the response cache middleware, so the `ETag` is computed for the page each visitor actually receives:

```php
use Spatie\ResponseCache\Middlewares\CacheResponse;

Route::get('/posts', [PostController::class, 'index'])
    ->middleware(['cache.headers:etag', CacheResponse::for(minutes(10))]);
```

Pages that contain a CSRF token differ per visitor. The package removes a stored `ETag` from any cached response that a replacer changed, so every visitor gets an `ETag` that matches their own page.

Only use `etag` here. Options like `public` or `max_age` let browsers and proxies reuse a page without asking your app, which isn't safe for pages with CSRF tokens or user-specific content.

## Serve public pages before PHP runs

For pages that are identical for every visitor, a cache in front of your app can serve them without starting PHP at all. Examples are Nginx's FastCGI cache, Varnish, or a CDN such as Cloudflare. This is the biggest possible speed-up for those pages.

Be careful with what you cache there:

- Laravel's `web` middleware group adds a session cookie to every response. Proxies and CDNs usually skip responses that set cookies, and they shouldn't store them, because the cookie belongs to one visitor.
- Pages with a CSRF token, a logged-in user's name or other per-visitor content must never be cached in front of your app.

Keep this package for those pages. It caches per user and swaps in each visitor's CSRF token, which a cache in front of your app can't do.

## Make Laravel itself faster

Every cached response still pays for Laravel booting. In production, cache your configuration and routes:

```bash
php artisan config:cache
php artisan route:cache
```

[Laravel Octane](https://laravel.com/docs/12.x/octane) keeps your application in memory between requests, which removes most of the boot time.
