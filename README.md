# Speed up a Laravel application by caching the entire response

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/laravel-responsecache/master.svg?style=flat-square)](https://travis-ci.org/spatie/laravel-responsecache)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/2a710105-29e4-410b-892f-6dfb89220172.svg?style=flat-square)](https://insight.sensiolabs.com/projects/2a710105-29e4-410b-892f-6dfb89220172)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-responsecache.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-responsecache)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)

This package can cache an entire response. 

## Installation

You can install the package via composer:
``` bash
$ composer require spatie/laravel-responsecache
```

Next, you must install the service provider:

```php
// config/app.php
'providers' => [
    ...
    Spatie\ReponseCache\ReponseCacheServiceProvider::class,
];
```

You can publish the config-file with:
```bash
php artisan vendor:publish --provider="Spatie\ResponseCache\ResponseCacheServiceProvider"
```

This is the contents of the published config file:

```php
return [

    /**
     *  The given class will determinate if a request should be cached. The
     *  default class will cache all GET-requests.
     *
     *  You can provide your own class given that it implements the
     *  CacheProfile interface.
     */
    'cacheProfile' => Spatie\ResponseCache\CacheProfiles\CacheAllGetRequests::class,

    /**
     * When using the default CacheRequestFilter this setting controls the
     * number of minutes responses must be cached.
     */
    'cacheLifetimeInMinutes' => 5,

    /*
     * This setting determines if a http header named "Laravel-reponsecache"
     * with the cache time should be added to a cached response. This
     * can be handy when debugging.
     */
    'addCacheTimeHeader' => true
];

```

## Usage

###Basic usage

By default the package will cache all `get`-request for five minutes. Logged in users will each have their own seperate cache. If you just want this behaviour, you're done: installing the `ResponseCacheServerProvider` was enough.


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
