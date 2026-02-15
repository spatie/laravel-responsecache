<div align="left">
    <a href="https://spatie.be/open-source?utm_source=github&utm_medium=banner&utm_campaign=laravel-responsecache">
      <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://spatie.be/packages/header/laravel-responsecache/html/dark.webp">
        <img alt="Logo for laravel-responsecache" src="https://spatie.be/packages/header/laravel-responsecache/html/light.webp">
      </picture>
    </a>

<h1>Speed up an app by caching the entire response</h1>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-responsecache/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/spatie/laravel-responsecache/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-responsecache/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/spatie/laravel-responsecache/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)

</div>

This Laravel package can cache an entire response. By default it will cache all successful GET requests that return text based content (such as HTML and JSON) for a week. This could potentially speed up the response quite considerably.

The first time a request comes in, the package will save the response before sending it to the user. When the same request comes in again, the cached response is returned without going through the entire application.

Here's a quick example:

```php
use Spatie\ResponseCache\Middlewares\CacheResponse;

Route::middleware(CacheResponse::for(minutes(10)))->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post}', [PostController::class, 'show']);
});
```

You can also use stale-while-revalidate caching for data that can be briefly stale:

```php
use Spatie\ResponseCache\Middlewares\FlexibleCacheResponse;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(FlexibleCacheResponse::for(lifetime: minutes(3), grace: minutes(12)));
```

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-responsecache.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-responsecache)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Documentation

All documentation is available [on our documentation site](https://spatie.be/docs/laravel-responsecache).

## Testing

You can run the tests with:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
