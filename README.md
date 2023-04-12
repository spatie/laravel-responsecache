<p align="center"><img src="/art/socialcard.png" alt="Social Card of Laravel Response Cache"></p>

# Speed up an app by caching the entire response

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
![Tests](https://github.com/spatie/laravel-responsecache/actions/workflows/run-tests.yml/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-responsecache.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-responsecache)

This Laravel package can cache an entire response. By default it will cache all successful get-requests that return text based content (such as html and json) for a week. This could potentially speed up the response quite considerably.

So the first time a request comes in the package will save the response before sending it to the users. When the same request comes in again we're not going through the entire application but just respond with the saved response.

Are you a visual learner? Then watch [this video](https://spatie.be/videos/laravel-package-training/laravel-responsecache) that covers how you can use laravel-responsecache and how it works under the hood.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-responsecache.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-responsecache)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

> If you're using PHP 7, install v6.x of this package.

You can install the package via composer:
```bash
composer require spatie/laravel-responsecache
```

The package will automatically register itself.

You can publish the config file with:
```bash
php artisan vendor:publish --tag="responsecache-config"
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
     *  Optionally, you can specify a header that will force a cache bypass.
     *  This can be useful to monitor the performance of your application.
     */
    'cache_bypass_header' => [
        'name' => env('CACHE_BYPASS_HEADER_NAME', null),
        'value' => env('CACHE_BYPASS_HEADER_VALUE', null),
    ],

    /*
     * When using the default CacheRequestFilter this setting controls the
     * default number of seconds responses must be cached.
     */
    'cache_lifetime_in_seconds' => env('RESPONSE_CACHE_LIFETIME', 60 * 60 * 24 * 7),

    /*
     * This setting determines if a http header named with the cache time
     * should be added to a cached response. This can be handy when
     * debugging.
     */
    'add_cache_time_header' => env('APP_DEBUG', true),

    /*
     * This setting determines the name of the http header that contains
     * the time at which the response was cached
     */
    'cache_time_header_name' => env('RESPONSE_CACHE_HEADER_NAME', 'laravel-responsecache'),

    /*
     * This setting determines if a http header named with the cache age
     * should be added to a cached response. This can be handy when
     * debugging.
     * ONLY works when "add_cache_time_header" is also active!
     */
    'add_cache_age_header' => env('RESPONSE_CACHE_AGE_HEADER', false),

    /*
     * This setting determines the name of the http header that contains
     * the age of cache
     */
    'cache_age_header_name' => env('RESPONSE_CACHE_AGE_HEADER_NAME', 'laravel-responsecache-age'),

    /*
     * Here you may define the cache store that should be used to store
     * requests. This can be the name of any store that is
     * configured in app/config/cache.php
     */
    'cache_store' => env('RESPONSE_CACHE_DRIVER', 'file'),

    /*
     * Here you may define replacers that dynamically replace content from the response.
     * Each replacer must implement the Replacer interface.
     */
    'replacers' => [
        \Spatie\ResponseCache\Replacers\CsrfTokenReplacer::class,
    ],

    /*
     * If the cache driver you configured supports tags, you may specify a tag name
     * here. All responses will be tagged. When clearing the responsecache only
     * items with that tag will be flushed.
     *
     * You may use a string or an array here.
     */
    'cache_tag' => '',

    /*
     * This class is responsible for generating a hash for a request. This hash
     * is used to look up an cached response.
     */
    'hasher' => \Spatie\ResponseCache\Hasher\DefaultHasher::class,

    /*
     * This class is responsible for serializing responses.
     */
    'serializer' => \Spatie\ResponseCache\Serializers\DefaultSerializer::class,
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

protected $middlewareAliases = [
   ...
   'doNotCacheResponse' => \Spatie\ResponseCache\Middlewares\DoNotCacheResponse::class,
];

```

## Usage

### Basic usage

By default, the package will cache all successful `GET` requests for a week.
Logged in users will each have their own separate cache. If this behaviour is what you
 need, you're done: installing the `ResponseCacheServiceProvider` was enough.

### Clearing the cache

#### Manually

The entire cache can be cleared with:
```php
ResponseCache::clear();
```
This will clear everything from the cache store specified in the config file.

#### Using a console command

The same can be accomplished by issuing this artisan command:

```bash
php artisan responsecache:clear
```

#### Using model events

You can leverage model events to clear the cache whenever a model is saved or deleted. Here's an example.
```php
namespace App\Traits;

use Spatie\ResponseCache\Facades\ResponseCache;

trait ClearsResponseCache
{
    public static function bootClearsResponseCache()
    {
        self::created(function () {
            ResponseCache::clear();
        });

        self::updated(function () {
            ResponseCache::clear();
        });

        self::deleted(function () {
            ResponseCache::clear();
        });
    }
}
```

### Forget one or several specific URIs

You can forget specific URIs with:
```php
// Forget one
ResponseCache::forget('/some-uri');

// Forget several
ResponseCache::forget(['/some-uri', '/other-uri']);

// Equivalent to the example above
ResponseCache::forget('/some-uri', '/other-uri');
```

The `ResponseCache::forget` method only works when you're not using a `cacheNameSuffix` in your cache profile, 
use `ResponseCache::selectCachedItems` to deal with `cacheNameSuffix`.

### Forgetting a selection of cached items

You can use `ResponseCache::selectCachedItems()` to specify which cached items should be forgotten.

```php

// forgetting all PUT responses of /some-uri
ResponseCache::selectCachedItems()->withPutMethod()->forUrls('/some-uri')->forget();

// forgetting all PUT responses of multiple endpoints
ResponseCache::selectCachedItems()->withPutMethod()->forUrls(['/some-uri','/other-uri'])->forget();

// this is equivalent to the example above
ResponseCache::selectCachedItems()->withPutMethod()->forUrls('/some-uri','/other-uri')->forget();

// forget /some-uri cached with "100" suffix (by default suffix is user->id or "")
ResponseCache::selectCachedItems()->usingSuffix('100')->forUrls('/some-uri')->forget();

// all options combined
ResponseCache::selectCachedItems()
    ->withPutMethod()
    ->withHeaders(['foo'=>'bar'])
    ->withCookies(['cookie1' => 'value'])
    ->withParameters(['param1' => 'value'])
    ->withRemoteAddress('127.0.0.1')
    ->usingSuffix('100') 
    ->usingTags('tag1', 'tag2')
    ->forUrls('/some-uri', '/other-uri')
    ->forget();

```

The `cacheNameSuffix` depends by your cache profile, by default is the user ID or an empty string if not authenticated.

### Preventing a request from being cached

Requests can be ignored by using the `doNotCacheResponse` middleware.
This middleware [can be assigned to routes and controllers](http://laravel.com/docs/master/controllers#controller-middleware).

Using the middleware are route could be exempt from being cached.

```php
// app/Http/routes.php

Route::get('/auth/logout', ['middleware' => 'doNotCacheResponse', 'uses' => 'AuthController@getLogout']);
```

Alternatively, you can add the middleware to a controller:

```php
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('doNotCacheResponse', ['only' => ['fooAction', 'barAction']]);
    }
}
```

### Purposefully bypassing the cache

It's possible to purposefully and securely bypass the cache and ensure you always receive a fresh response. This may be useful in case you want to profile some endpoint or in case you need to debug a response.
In any case, all you need to do is fill the `CACHE_BYPASS_HEADER_NAME` and `CACHE_BYPASS_HEADER_VALUE` environment variables and then use that custom header when performing the requests.

### Creating a custom cache profile
To determine which requests should be cached, and for how long, a cache profile class is used.
The default class that handles these questions is `Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests`.

You can create your own cache profile class by implementing the `
Spatie\ResponseCache\CacheProfiles\CacheProfile` interface. Let's take a look at the interface:

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
    public function useCacheNameSuffix(Request $request);
}
```

### Caching specific routes
Instead of registering the `cacheResponse` middleware globally, you can also register it as route middleware.

```php
protected $middlewareAliases = [
   ...
   'cacheResponse' => \Spatie\ResponseCache\Middlewares\CacheResponse::class,
];
```

When using the route middleware you can specify the number of seconds these routes should be cached:

```php
// cache this route for 5 minutes
Route::get('/my-special-snowflake', 'SnowflakeController@index')->middleware('cacheResponse:300');

// cache all these routes for 10 minutes
Route::group(function() {
   Route::get('/another-special-snowflake', 'AnotherSnowflakeController@index');

   Route::get('/yet-another-special-snowflake', 'YetAnotherSnowflakeController@index');
})->middleware('cacheResponse:600');
```

### Using tags

If the [cache driver you configured supports tags](https://laravel.com/docs/5.8/cache#cache-tags), you can specify a list of tags when applying the middleware.

```php
// add a "foo" tag to this route with a 300 second lifetime
Route::get('/test1', 'SnowflakeController@index')->middleware('cacheResponse:300,foo');

// add a "bar" tag to this route
Route::get('/test2', 'SnowflakeController@index')->middleware('cacheResponse:bar');

// add both "foo" and "bar" tags to these routes
Route::group(function() {
   Route::get('/test3', 'AnotherSnowflakeController@index');

   Route::get('/test4', 'YetAnotherSnowflakeController@index');
})->middleware('cacheResponse:foo,bar');
```

#### Clearing tagged content

You can clear responses which are assigned a tag or list of tags. For example, this statement would remove the `'/test3'` and `'/test4'` routes above:

```php
ResponseCache::clear(['foo', 'bar']);
```

In contrast, this statement would remove only the `'/test2'` route:

```php
ResponseCache::clear(['bar']);
```

Note that this uses [Laravel's built in cache tags](https://laravel.com/docs/master/cache#cache-tags) functionality, meaning
routes can also be cleared in the usual way:

```php
Cache::tags('special')->flush();
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

### Creating a Replacer

To replace cached content by dynamic content, you can create a replacer.
By default we add a `CsrfTokenReplacer` in the config file.

You can create your own replacers by implementing the `Spatie\ResponseCache\Replacers\Replacer` interface. Let's take a look at the interface:

```php
interface Replacer
{
    /*
     * Prepare the initial response before it gets cached.
     *
     * For example: replace a generated csrf_token by '<csrf-token-here>' that you can
     * replace with its dynamic counterpart when the cached response is returned.
     */
    public function prepareResponseToCache(Response $response): void;

    /*
     * Replace any data you want in the cached response before it gets
     * sent to the browser.
     *
     * For example: replace '<csrf-token-here>' by a call to csrf_token()
     */
    public function replaceInCachedResponse(Response $response): void;
}
```

Afterwards you can define your replacer in the `responsecache.php` config file:

```
/*
 * Here you may define replacers that dynamically replace content from the response.
 * Each replacer must implement the Replacer interface.
 */
'replacers' => [
    \Spatie\ResponseCache\Replacers\CsrfTokenReplacer::class,
],
```

### Customizing the serializer

A serializer is responsible from serializing a response so it can be stored in the cache. It is also responsible for rebuilding the response from the cache.

The default serializer `Spatie\ResponseCache\Serializer\DefaultSerializer` will just work in most cases.

If you have some special serialization needs you can specify a custom serializer in the `serializer` key of the config file. Any class that implements `Spatie\ResponseCache\Serializers\Serializer` can be used. This is how that interface looks like:

```php
namespace Spatie\ResponseCache\Serializers;

use Symfony\Component\HttpFoundation\Response;

interface Serializer
{
    public function serialize(Response $response): string;

    public function unserialize(string $serializedResponse): Response;
}
```
## Testing

You can run the tests with:
``` bash
composer test
```

## Alternatives

- [Barry Vd. Heuvel](https://twitter.com/barryvdh) made [a package that caches responses by leveraging HttpCache](https://github.com/barryvdh/laravel-httpcache).
- [Joseph Silber](https://twitter.com/joseph_silber) created [Laravel Page Cache](https://github.com/JosephSilber/page-cache) that can write it's cache to disk and let Nginx read them, so PHP doesn't even have to start up anymore.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

And a special thanks to [Caneco](https://twitter.com/caneco) for the logo ✨

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
