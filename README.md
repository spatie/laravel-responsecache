# Speed up an app by caching the entire response

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/laravel-responsecache/master.svg?style=flat-square)](https://travis-ci.org/spatie/laravel-responsecache)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-responsecache.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-responsecache)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)

This Laravel 5.5 package can cache an entire response. By default it will cache all successful get-requests for a week. This could potentially speed up the response quite considerably.

So the first time a request comes in the package will save the response before sending it to the users. When the same request comes in again we're not going through the entire application but just respond with the saved response.


If you're using Laravel 5.1, 5.2 or 5.3 check out the [v1 branch](https://github.com/spatie/laravel-responsecache/tree/v1).

If you're using Laravel 5.4 check out the [v2 branch](https://github.com/spatie/laravel-responsecache/tree/v2).


Spatie is a webdesign agency in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Installation

You can install the package via composer:
``` bash
$ composer require spatie/laravel-responsecache
```

The package will automatically register itself.

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Spatie\ResponseCache\ResponseCacheServiceProvider"
```

This is the contents of the published config file:

```php
// config/responsecache.php

return [
    /*
     * Determine if the response cache middleware should be enabled.
     */
    'enabled' => env('RESPONSE_CACHE_ENABLED', true),

    /*
     *  The given class will determinate if a request should be cached. The
     *  default class will cache all successful GET-requests.
     *
     *  You can provide your own class given that it implements the
     *  CacheProfile interface.
     */
    'cache_profile' => Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests::class,

    /*
     * When using the default CacheRequestFilter this setting controls the
     * default number of minutes responses must be cached.
     */
    'cache_lifetime_in_minutes' => env('RESPONSE_CACHE_LIFETIME', 60 * 24 * 7),

    /*
     * This setting determines if a http header named "Laravel-responsecache"
     * with the cache time should be added to a cached response. This
     * can be handy when debugging.
     */
    'add_cache_time_header' => env('APP_DEBUG', true),

    /*
     * Here you may define the cache store that should be used to store
     * requests. This can be the name of any store that is
     * configured in app/config/cache.php
     */
    'cache_store' => env('RESPONSE_CACHE_DRIVER', 'file'),
    
    /*
     * If the cache driver you configured supports tags, you may specify a tag name
     * here. All responses will be tagged. When clearing the responsecache only
     * items with that tag will be cleared.
     *
     * You may use a string or an array here.
     */
    'cache_tag' => '',
];
```

And finally you should install the provided middlewares `\Spatie\ResponseCache\Middlewares\CacheResponse::class` and `\Spatie\ResponseCache\Middlewares\DoNotCacheResponse` in the http kernel. 


```php
// app/Http/Kernel.php

...

protected $middlewareGroups = [
   'web' => [
       ...
       \Spatie\ResponseCache\Middlewares\CacheResponse::class,
   ],

...

protected $routeMiddleware = [
   ...
   'doNotCacheResponse' => \Spatie\ResponseCache\Middlewares\DoNotCacheResponse::class,
];

```

## Usage

### Basic usage

By default the package will cache all successful `get`-requests for a week.
Logged in users will each have their own separate cache. If this behaviour is what you
 need, you're done: installing the `ResponseCacheServerProvider` was enough.

### Clearing the cache
The entire cache can be cleared with:
```php
ResponseCache::clear();
```
This will clear everything from the cache store specified in the config-file.

The same can be accomplished by issuing this artisan command:

```bash
$ php artisan responsecache:clear
```

### Forget one or several specific URI(s)

You can forget specific URIs with:
```php
// Forget one URI
ResponseCache::forget('/some-uri');

// Forget several URIs
ResponseCache::forget(['/some-uri', '/other-uri']);

// Alternatively
ResponseCache::forget('/some-uri', '/other-uri');
```

### Preventing a request from being cached
Requests can be ignored by using the `doNotCacheResponse`-middleware. 
This middleware [can be assigned to routes and controllers]
(http://laravel.com/docs/master/controllers#controller-middleware).

Using the middleware are route could be exempt from being cached.

```php
// app/Http/routes.php

Route::get('/auth/logout', ['middleware' => 'doNotCacheResponse', 'uses' => 'AuthController@getLogout']);
```

Alternatively you can add the middleware to a controller:

```php
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('doNotCacheResponse', ['only' => ['fooAction', 'barAction']]);
    }
}
```


### Creating a custom cache profile
To determine which requests should be cached, and for how long, a cache profile class is used. 
The default class that handles these questions is `Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests`. 

You can create your own cache profile class by implementing the `
Spatie\ResponseCache\CacheProfiles\CacheProfile`-interface. Let's take a look at the interface:

```php
interface CacheProfile
{
    /*
     * Determine if the response cache middleware should be enabled.
     */
    public function enabled(Request $request): bool;

    /*
     * Determine if the given request should be cached.
     */
    public function shouldCacheRequest(Request $request): bool;

    /*
     * Determine if the given response should be cached.
     */
    public function shouldCacheResponse(Response $response): bool;

    /*
     * Return the time when the cache must be invalidated.
     */
    public function cacheRequestUntil(Request $request): DateTime;

    /**
     * Return a string to differentiate this request from others.
     *
     * For example: if you want a different cache per user you could return the id of
     * the logged in user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function cacheNameSuffix(Request $request);
}
```

### Caching specific routes
Instead of registering the `cacheResponse` middleware globally, you can also register it as route middleware.

```php
protected $routeMiddleware = [
   ...
   'cacheResponse' => \Spatie\ResponseCache\Middlewares\CacheResponse::class,
];
```

When using the route middleware you can specify the number of minutes these routes should be cached:

```php
// cache this route for 5 minutes
Route::get('/my-special-snowflake', 'SnowflakeController@index')->middleware('cacheResponse:5');

// cache all these routes for 10 minutes
Route::group(function() {
   Route::get('/another-special-snowflake', 'AnotherSnowflakeController@index');
   
   Route::get('/yet-another-special-snowflake', 'YetAnotherSnowflakeController@index');
})->middleware('cacheResponse:10');
```

### Events

There are several events you can use to monitor and debug response caching in your application.

#### ResponseCacheHit

`Spatie\ResponseCache\Events\ResponseCacheHit`

This event is fired when a request passes through the `ResponseCache` middleware and a cached response was found and returned.

#### CacheMissed

`Spatie\ResponseCache\Events\CacheMissed`

This event is fired when a request passes through the `ResponseCache` middleware but no cached response was found or returned.

#### ClearingResponseCache and ClearedResponseCache

`Spatie\ResponseCache\Events\ClearingResponseCache`

`Spatie\ResponseCache\Events\ClearedResponseCache`

These events are fired respectively when the `responsecache:clear` is started and finished.

### CSRF Tokens

When a response is cached and a CSRF token exists on the page, it too will be cached and cause token mismatch or page expired errors. You can't reliably cache the response for the entire page when using forms that require CSRF tokens because the tokens will never match.

It is recommended that you disable response caching for pages where forms exists to avoid these errors.

Alternatively, but not recommended, you may disable CSRF protection on a per-route basis. It is highly unadvisable to disable CSRF for user-authenticated pages at the risk of cross-site request forgery.

See how to disable CSRF on per-route basis here: https://laracasts.com/discuss/channels/laravel/disabling-csrf-for-a-specific-route-in-laravel-5

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing
You can run the tests with:
``` bash
$ composer test
```

## Alternatives
- [Barry Vd. Heuvel](https://twitter.com/barryvdh) made [a package that caches responses by leveraging HttpCache](https://github.com/barryvdh/laravel-httpcache).

- spatie/laravel-responsecache is tied to Laravel 5.1. If you need this functionality in Laravel 4
take a look at [Flatten](https://github.com/Anahkiasen/flatten) by [Maxime Fabre](https://twitter.com/Anahkiasen).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## Support us

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

Does your business depend on our contributions? Reach out and support us on [Patreon](https://www.patreon.com/spatie). 
All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
