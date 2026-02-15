<?php

use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Test\Concerns\CanChangeCacheStore;

use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\isTrue;

uses(CanChangeCacheStore::class);

it('can cache requests using route cache tags', function () {
    $firstResponse = $this->get('/tagged/1');
    assertRegularResponse($firstResponse);

    $secondResponse = $this->get('/tagged/1');
    assertCachedResponse($secondResponse);
    assertSameResponse($firstResponse, $secondResponse);

    $thirdResponse = $this->get('/tagged/2');
    assertRegularResponse($thirdResponse);

    $fourthResponse = $this->get('/tagged/2');
    assertCachedResponse($fourthResponse);
    assertSameResponse($thirdResponse, $fourthResponse);
});

it('can forget requests using route cache tags', function () {
    $firstResponse = $this->get('/tagged/1');
    assertRegularResponse($firstResponse);

    $this->app['responsecache']->clear(['foo']);

    $secondResponse = $this->get('/tagged/1');
    assertRegularResponse($secondResponse);
    assertDifferentResponse($firstResponse, $secondResponse);

    $this->app['responsecache']->clear();

    $thirdResponse = $this->get('/tagged/1');
    assertRegularResponse($thirdResponse);
    assertDifferentResponse($secondResponse, $thirdResponse);
});

it('can forget requests using route cache tags from global cache', function () {
    $firstResponse = $this->get('/tagged/1');
    assertRegularResponse($firstResponse);

    $this->app['cache']->store(config('responsecache.cache.store'))->tags('laravel-responsecache')->clear('foo');

    $secondResponse = $this->get('/tagged/1');
    assertRegularResponse($secondResponse);
    assertDifferentResponse($firstResponse, $secondResponse);
});

it('can forget requests using route cache tags without deleting unrelated cache', function () {
    $this->app['cache']->store(config('responsecache.cache.store'))->tags('unrelated-cache')->put('baz', true);

    $firstResponse = $this->get('/tagged/1');
    assertRegularResponse($firstResponse);

    $this->app['responsecache']->clear();

    $secondResponse = $this->get('/tagged/1');
    assertRegularResponse($secondResponse);
    assertDifferentResponse($firstResponse, $secondResponse);

    $cacheValue = $this->app['cache']->store(config('responsecache.cache.store'))->tags('unrelated-cache')->get('baz');
    assertThat($cacheValue, isTrue(), 'Failed to assert that a cached value is present');
});

it('can forget requests using multiple route cache tags', function () {
    $firstResponse = $this->get('/tagged/2');
    assertRegularResponse($firstResponse);

    $this->app['responsecache']->clear(['bar']);

    $secondResponse = $this->get('/tagged/2');
    assertRegularResponse($secondResponse);
    assertDifferentResponse($firstResponse, $secondResponse);
});

it('can generate middleware string for different tag combinations using the using method', function () {
    $singleTag = CacheResponse::using('foo');
    expect($singleTag)->toBe(CacheResponse::class.':foo');

    $multipleTags = CacheResponse::using('foo', 'bar');
    expect($multipleTags)->toBe(CacheResponse::class.':foo,bar');

    $lifetime = CacheResponse::using(300);
    expect($lifetime)->toBe(CacheResponse::class.':300');
});
