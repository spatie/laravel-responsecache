<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Hasher\RequestHasher;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
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
    assertTrue($this->app['responsecache']->shouldCache($request, createResponse(200)));
});

it('will forget requests older than the lifetime', function () {
    config()->set('responsecache.minimum_requests.lifetime_in_seconds', 60);

    $this->get('/random');

    Carbon::setTestNow(Carbon::now()->addSeconds(61));

    $this->get('/random');
    $thirdResponse = $this->get('/random');

    assertRegularResponse($thirdResponse);

    Carbon::setTestNow();
});

it('can keep the request counters on a separate store', function () {
    config()->set('responsecache.minimum_requests.store', 'array');

    $this->get('/random');

    $key = 'responsecache-requests-'.app(RequestHasher::class)->getHashFor(Request::create('/random'));

    assertSame(1, Cache::store('array')->get($key));
    assertNull(Cache::store(config('responsecache.cache.store'))->get($key));
});
