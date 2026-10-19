<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Spatie\ResponseCache\Facades\ResponseCache;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
    config()->set('responsecache.minimum_requests.count', 2);
});

it('will cache a response once its request has been seen the minimum number of times', function () {
    $firstResponse = $this->get('/random');
    $secondResponse = $this->get('/random');
    $thirdResponse = $this->get('/random');

    assertRegularResponse($firstResponse);
    assertRegularResponse($secondResponse);
    assertCachedResponse($thirdResponse);

    assertSameResponse($secondResponse, $thirdResponse);
});

it('only counts a request once when cache middleware overlaps', function () {
    config()->set('responsecache.cache.store', 'array');

    $firstResponse = $this->get('/tagged/1');
    $secondResponse = $this->get('/tagged/1');
    $thirdResponse = $this->get('/tagged/1');

    assertRegularResponse($firstResponse);
    assertRegularResponse($secondResponse);
    assertCachedResponse($thirdResponse);

    assertSameResponse($secondResponse, $thirdResponse);
});

it('also waits for the minimum when using flexible caching', function () {
    assertRegularResponse($this->get('/flexible/basic'));

    $this->get('/flexible/basic')->assertHeader('X-Cache-Status', 'MISS');
    $this->get('/flexible/basic')->assertHeader('X-Cache-Status', 'HIT');
});

it('can skip a deferred refresh without reporting an error after the counter expires', function () {
    Exceptions::fake();

    config()->set('responsecache.minimum_requests.lifetime_in_seconds', 2);

    $this->get('/flexible/basic');
    $cachedResponse = $this->get('/flexible/basic');

    $this->travel(6)->seconds();

    $staleResponse = $this->get('/flexible/basic');
    $secondStaleResponse = $this->get('/flexible/basic');
    $refreshedResponse = $this->get('/flexible/basic');

    assertSameResponse($cachedResponse, $staleResponse);
    assertSameResponse($cachedResponse, $secondStaleResponse);
    assertDifferentResponse($cachedResponse, $refreshedResponse);
    Exceptions::assertNothingReported();
});

it('will count requests per cache key', function () {
    $this->get('/random/1');
    $this->get('/random/1');

    $firstResponse = $this->get('/random/2');
    $secondResponse = $this->get('/random/2');

    assertRegularResponse($firstResponse);
    assertRegularResponse($secondResponse);
});

it('will not count a request whose response cannot be cached', function () {
    $request = createRequest('get');

    assertFalse($this->app['responsecache']->shouldCache($request, createResponse(500)));
    assertFalse($this->app['responsecache']->shouldCache($request, createResponse(200)));
    assertTrue($this->app['responsecache']->shouldCache(createRequest('get'), createResponse(200)));
});

it('will forget requests older than the lifetime', function () {
    config()->set('responsecache.minimum_requests.count', 3);
    config()->set('responsecache.minimum_requests.lifetime_in_seconds', 60);

    $this->get('/random');

    $this->travel(59)->seconds();

    $this->get('/random');

    $this->travel(2)->seconds();

    $thirdResponse = $this->get('/random');
    $fourthResponse = $this->get('/random');
    $fifthResponse = $this->get('/random');
    $sixthResponse = $this->get('/random');

    assertRegularResponse($thirdResponse);
    assertRegularResponse($fourthResponse);
    assertRegularResponse($fifthResponse);
    assertCachedResponse($sixthResponse);

    assertSameResponse($fifthResponse, $sixthResponse);
});

it('restarts the lifetime if a counter expires while being incremented', function () {
    Cache::extend('expiring-array', fn () => Cache::repository(new class extends ArrayStore
    {
        protected bool $expireOnIncrement = true;

        public function increment($key, $value = 1): int
        {
            if ($this->expireOnIncrement) {
                $this->forget($key);
                $this->expireOnIncrement = false;
            }

            return parent::increment($key, $value);
        }
    }));

    config()->set('cache.stores.expiring-array', ['driver' => 'expiring-array']);
    config()->set('responsecache.minimum_requests.store', 'expiring-array');
    config()->set('responsecache.minimum_requests.lifetime_in_seconds', 60);

    $this->get('/random');
    $this->get('/random');

    $this->travel(61)->seconds();

    $thirdResponse = $this->get('/random');
    $fourthResponse = $this->get('/random');
    $fifthResponse = $this->get('/random');

    assertRegularResponse($thirdResponse);
    assertRegularResponse($fourthResponse);
    assertCachedResponse($fifthResponse);
});

it('can keep the request counters on a separate store', function () {
    config()->set('responsecache.minimum_requests.store', 'array');

    $this->get('/random');

    ResponseCache::clear();

    $secondResponse = $this->get('/random');
    $thirdResponse = $this->get('/random');

    assertRegularResponse($secondResponse);
    assertCachedResponse($thirdResponse);
    assertSameResponse($secondResponse, $thirdResponse);
});
