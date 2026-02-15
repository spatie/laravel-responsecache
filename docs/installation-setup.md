---
title: Installation & setup
weight: 4
---

You can install the package via composer:

```bash
composer require spatie/laravel-responsecache
```

The package will automatically register itself.

## Registering the middleware

Add the `CacheResponse` middleware and the `DoNotCacheResponse` alias in `bootstrap/app.php`:

```php
// bootstrap/app.php

use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        CacheResponse::class,
    ]);

    $middleware->alias([
        'doNotCacheResponse' => DoNotCacheResponse::class,
    ]);
})
```

By default, the package will now cache all successful GET requests that return text based content (such as HTML and JSON) for a week.

## Publishing the config file

Optionally, you can publish the config file with:

```bash
php artisan vendor:publish --tag="responsecache-config"
```

This is the content of the published config file:

```php
return [
    'enabled' => env('RESPONSE_CACHE_ENABLED', true),

    'cache' => [
        'store' => env('RESPONSE_CACHE_DRIVER', 'file'),
        'lifetime_in_seconds' => (int) env('RESPONSE_CACHE_LIFETIME', 60 * 60 * 24 * 7),
        'tag' => env('RESPONSE_CACHE_TAG', ''),
    ],

    'bypass' => [
        'enabled' => env('CACHE_BYPASS_HEADER_NAME') !== null,
        'header_name' => env('CACHE_BYPASS_HEADER_NAME'),
        'header_value' => env('CACHE_BYPASS_HEADER_VALUE'),
    ],

    'debug' => [
        'add_time_header' => env('APP_DEBUG', false),
        'time_header_name' => env('RESPONSE_CACHE_HEADER_NAME', 'laravel-responsecache'),
        'add_age_header' => env('RESPONSE_CACHE_AGE_HEADER', false),
        'age_header_name' => env('RESPONSE_CACHE_AGE_HEADER_NAME', 'laravel-responsecache-age'),
    ],

    'cache_profile' => Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests::class,

    'hasher' => \Spatie\ResponseCache\Hasher\DefaultHasher::class,

    'serializer' => \Spatie\ResponseCache\Serializers\JsonSerializer::class,

    'replacers' => [
        \Spatie\ResponseCache\Replacers\CsrfTokenReplacer::class,
    ],
];
```
