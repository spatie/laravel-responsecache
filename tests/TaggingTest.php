<?php

namespace Spatie\ResponseCache\Test;

use Spatie\ResponseCache\Test\Concerns\CanChangeCacheStore;
use function PHPUnit\Framework\isTrue;
use function PHPUnit\Framework\assertThat;

uses(CanChangeCacheStore::class);

test('it can cache requests using route cache tags', function () {
    $firstResponse = $this->get('/tagged/1');
    $this->assertRegularResponse($firstResponse);

    $secondResponse = $this->get('/tagged/1');
    $this->assertCachedResponse($secondResponse);
    $this->assertSameResponse($firstResponse, $secondResponse);

    $thirdResponse = $this->get('/tagged/2');
    $this->assertRegularResponse($thirdResponse);

    $fourthResponse = $this->get('/tagged/2');
    $this->assertCachedResponse($fourthResponse);
    $this->assertSameResponse($thirdResponse, $fourthResponse);
});

test('it can forget requests using route cache tags', function () {
    $firstResponse = $this->get('/tagged/1');
    $this->assertRegularResponse($firstResponse);

    $this->app['responsecache']->clear(['foo']);

    $secondResponse = $this->get('/tagged/1');
    $this->assertRegularResponse($secondResponse);
    $this->assertDifferentResponse($firstResponse, $secondResponse);

    $this->app['responsecache']->clear();

    $thirdResponse = $this->get('/tagged/1');
    $this->assertRegularResponse($thirdResponse);
    $this->assertDifferentResponse($secondResponse, $thirdResponse);
});

test('it can forget requests using route cache tags from global cache', function () {
    $firstResponse = $this->get('/tagged/1');
    $this->assertRegularResponse($firstResponse);

    $this->app['cache']->store(config('responsecache.cache_store'))->tags('laravel-responsecache')->clear('foo');

    $secondResponse = $this->get('/tagged/1');
    $this->assertRegularResponse($secondResponse);
    $this->assertDifferentResponse($firstResponse, $secondResponse);
});

test('it can forget requests using route cache tags without deleting unrelated cache', function () {
    $this->app['cache']->store(config('responsecache.cache_store'))->tags('unrelated-cache')->put('baz', true);

    $firstResponse = $this->get('/tagged/1');
    $this->assertRegularResponse($firstResponse);

    $this->app['responsecache']->clear();

    $secondResponse = $this->get('/tagged/1');
    $this->assertRegularResponse($secondResponse);
    $this->assertDifferentResponse($firstResponse, $secondResponse);

    $cacheValue = $this->app['cache']->store(config('responsecache.cache_store'))->tags('unrelated-cache')->get('baz');
    assertThat($cacheValue, isTrue(), 'Failed to assert that a cached value is present');
});

test('it can forget requests using multiple route cache tags', function () {
    $firstResponse = $this->get('/tagged/2');
    $this->assertRegularResponse($firstResponse);

    $this->app['responsecache']->clear(['bar']);

    $secondResponse = $this->get('/tagged/2');
    $this->assertRegularResponse($secondResponse);
    $this->assertDifferentResponse($firstResponse, $secondResponse);
});
