---
title: Introduction
weight: 1
---

This Laravel package can cache an entire response. By default it will cache all successful GET requests that return text based content (such as HTML and JSON) for a week. This could potentially speed up the response quite considerably.

The first time a request comes in, the package will save the response before sending it to the user. When the same request comes in again, the cached response is returned without going through the entire application. Logged in users will each have their own separate cache.

Here's a quick example:

```php
use Spatie\ResponseCache\Middlewares\CacheResponse;

Route::middleware(CacheResponse::for(minutes(10)))->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post}', [PostController::class, 'show']);
});
```

You can also use stale-while-revalidate (SWR) caching for data that can be briefly stale:

```php
use Spatie\ResponseCache\Middlewares\FlexibleCacheResponse;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(FlexibleCacheResponse::for(lifetime: minutes(3), grace: minutes(12)));
```

The cache can be cleared programmatically or via an artisan command.

```php
use Spatie\ResponseCache\Facades\ResponseCache;

ResponseCache::clear();
```

```bash
php artisan responsecache:clear
```

## We got badges

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-responsecache/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/spatie/laravel-responsecache/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-responsecache/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/spatie/laravel-responsecache/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)
