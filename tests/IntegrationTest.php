<?php

namespace Spatie\ResponseCache\Test;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Event;
use Spatie\ResponseCache\Events\CacheMissed;
use Spatie\ResponseCache\Events\ResponseCacheHit;
use Spatie\ResponseCache\Facades\ResponseCache;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

test('it will cache a get request', function () {
    $firstResponse = $this->get('/random');
    $secondResponse = $this->get('/random');

    $this->assertRegularResponse($firstResponse);
    $this->assertCachedResponse($secondResponse);

    $this->assertSameResponse($firstResponse, $secondResponse);
});

test('it will fire an event when responding without cache', function () {
    Event::fake();

    $this->get('/random');

    Event::assertDispatched(CacheMissed::class);
});

test('it will fire an event when responding from cache', function () {
    Event::fake();

    $this->get('/random');
    $this->get('/random');

    Event::assertDispatched(ResponseCacheHit::class);
});

test('it will cache redirects', function () {
    $firstResponse = $this->get('/redirect');
    $secondResponse = $this->get('/redirect');

    $this->assertRegularResponse($firstResponse);
    $this->assertCachedResponse($secondResponse);

    $this->assertSameResponse($firstResponse, $secondResponse);
});

test('it will not cache errors', function () {
    $firstResponse = $this->get('/notfound');
    $secondResponse = $this->get('/notfound');

    $this->assertRegularResponse($firstResponse);
    $this->assertRegularResponse($secondResponse);
});

test('it will not cache a post request', function () {
    $firstResponse = $this->call('POST', '/random');
    $secondResponse = $this->call('POST', '/random');

    $this->assertRegularResponse($firstResponse);
    $this->assertRegularResponse($secondResponse);

    $this->assertDifferentResponse($firstResponse, $secondResponse);
});

test('it can forget a specific cached request', function () {
    $firstResponse = $this->get('/random');
    $this->assertRegularResponse($firstResponse);

    ResponseCache::forget('/random');

    $secondResponse = $this->get('/random');
    $this->assertRegularResponse($secondResponse);

    $this->assertDifferentResponse($firstResponse, $secondResponse);
});

test('it can forget several specific cached requests at once', function () {
    $firstResponseFirstCall = $this->get('/random/1');
    $this->assertRegularResponse($firstResponseFirstCall);

    $secondResponseFirstCall = $this->get('/random/2');
    $this->assertRegularResponse($secondResponseFirstCall);

    ResponseCache::forget(['/random/1', '/random/2']);

    $firstResponseSecondCall = $this->get('/random/1');
    $this->assertRegularResponse($firstResponseSecondCall);

    $secondResponseSecondCall = $this->get('/random/2');
    $this->assertRegularResponse($secondResponseSecondCall);

    $this->assertDifferentResponse($firstResponseFirstCall, $firstResponseSecondCall);
    $this->assertDifferentResponse($secondResponseFirstCall, $secondResponseSecondCall);
});

test('it will cache responses for each logged in user separately', function () {
    $this->get('/login/1');
    $firstUserFirstCall = $this->get('/');
    $firstUserSecondCall = $this->get('/');
    $this->get('logout');

    $this->get('/login/2');
    $secondUserFirstCall = $this->get('/');
    $secondUserSecondCall = $this->get('/');
    $this->get('logout');

    $this->assertRegularResponse($firstUserFirstCall);
    $this->assertCachedResponse($firstUserSecondCall);

    $this->assertRegularResponse($secondUserFirstCall);
    $this->assertCachedResponse($secondUserSecondCall);

    $this->assertSameResponse($firstUserFirstCall, $firstUserSecondCall);
    $this->assertSameResponse($secondUserFirstCall, $secondUserSecondCall);

    $this->assertDifferentResponse($firstUserFirstCall, $secondUserSecondCall);
    $this->assertDifferentResponse($firstUserSecondCall, $secondUserSecondCall);
});

test('it will not cache routes with the doNotCacheResponse middleware', function () {
    $firstResponse = $this->get('/uncacheable');
    $secondResponse = $this->get('/uncacheable');

    $this->assertRegularResponse($firstResponse);
    $this->assertRegularResponse($secondResponse);

    $this->assertDifferentResponse($firstResponse, $secondResponse);
});

test('it will not cache request when the package is not enable', function () {
    $this->app['config']->set('responsecache.enabled', false);

    $firstResponse = $this->get('/random');
    $secondResponse = $this->get('/random');

    $this->assertRegularResponse($firstResponse);
    $this->assertRegularResponse($secondResponse);

    $this->assertDifferentResponse($firstResponse, $secondResponse);
});

test('it will not serve cached requests when it is disabled in the config file', function () {
    $firstResponse = $this->get('/random');

    $this->app['config']->set('responsecache.enabled', false);

    $secondResponse = $this->get('/random');

    $this->assertRegularResponse($firstResponse);
    $this->assertRegularResponse($secondResponse);

    $this->assertDifferentResponse($firstResponse, $secondResponse);
});

test('it will cache file responses', function () {
    $firstResponse = $this->get('/image');
    $secondResponse = $this->get('/image');

    $this->assertRegularResponse($firstResponse);
    $this->assertCachedResponse($secondResponse);

    $this->assertSameResponse($firstResponse, $secondResponse);
});

test('it wont cache if lifetime is 0', function () {
    $this->app['config']->set('responsecache.cache_lifetime_in_seconds', 0);

    $firstResponse = $this->get('/');
    $secondResponse = $this->get('/');

    $this->assertRegularResponse($firstResponse);
    $this->assertRegularResponse($secondResponse);
});

test('it will cache response for given lifetime which is defined as middleware parameter', function () {
    // Set default lifetime as 0 to check if it will cache for given lifetime
    $this->app['config']->set('responsecache.cache_lifetime_in_seconds', 0);

    $firstResponse = $this->get('/cache-for-given-lifetime');
    $secondResponse = $this->get('/cache-for-given-lifetime');

    $this->assertRegularResponse($firstResponse);
    $this->assertCachedResponse($secondResponse);
});

test('it will reproduce cache if given lifetime is expired', function () {
    // Set default lifetime as 0 to disable middleware that is already pushed to Kernel
    $this->app['config']->set('responsecache.cache_lifetime_in_seconds', 0);

    Carbon::setTestNow(Carbon::now()->subMinutes(6));
    $firstResponse = $this->get('/cache-for-given-lifetime');
    $this->assertRegularResponse($firstResponse);

    $secondResponse = $this->get('/cache-for-given-lifetime');
    $this->assertCachedResponse($secondResponse);

    Carbon::setTestNow();
    $thirdResponse = $this->get('/cache-for-given-lifetime');
    $this->assertRegularResponse($thirdResponse);
});

test('it can add a cache time header', function () {
    $this->app['config']->set('responsecache.add_cache_time_header', true);
    $this->app['config']->set('responsecache.cache_time_header_name', 'X-Cached-At');

    $firstResponse = $this->get('/random');
    $secondResponse = $this->get('/random');

    $this->assertFalse($firstResponse->headers->has('X-Cached-At'));
    assertTrue($secondResponse->headers->has('X-Cached-At'));
    $this->assertInstanceOf(DateTime::class, $secondResponse->headers->getDate('X-Cached-At'));

    $this->assertSameResponse($firstResponse, $secondResponse);
});

test('it can add a cache age header', function () {
    $this->app['config']->set('responsecache.add_cache_time_header', true);
    $this->app['config']->set('responsecache.add_cache_age_header', true);
    $this->app['config']->set('responsecache.cache_age_header_name', 'X-Cached-Age');

    $firstResponse = $this->get('/random');
    $secondResponse = $this->get('/random');

    assertFalse($firstResponse->headers->has('X-Cached-Age'));
    assertTrue($secondResponse->headers->has('X-Cached-Age'));

    $this->assertIsNumeric($secondResponse->headers->get('X-Cached-Age'));

    $this->assertSameResponse($firstResponse, $secondResponse);
});

test('it wont cache nor serve a cached response if request has bypass header', function () {
    $headerName = 'X-Cache-Bypass';
    $headerValue = rand(1, 99999);
    $this->app['config']->set('responsecache.cache_bypass_header.name', $headerName);
    $this->app['config']->set('responsecache.cache_bypass_header.value', $headerValue);

    $firstResponse = $this->get('/', ['X-Cache-Bypass' => $headerValue]);
    $secondResponse = $this->get('/', ['X-Cache-Bypass' => $headerValue]);

    $this->assertRegularResponse($firstResponse);
    $this->assertRegularResponse($secondResponse);
});
