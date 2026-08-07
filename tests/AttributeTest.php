<?php

use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Test\TestClasses\ClassLevelCacheInvokableController;
use Spatie\ResponseCache\Test\TestClasses\InvokableCacheController;
use Spatie\ResponseCache\Test\TestClasses\InvokableNoCacheController;

beforeEach(function () {
    // Disable the default lifetime, so only a lifetime coming from an attribute can cache a response.
    config()->set('responsecache.cache.lifetime_in_seconds', 0);
});

it('reads a method level attribute from an invokable controller registered without a method', function () {
    Route::any('/invokable', InvokableCacheController::class)->middleware(CacheResponse::class);

    assertRegularResponse($this->get('/invokable'));
    assertCachedResponse($this->get('/invokable'));
});

it('reads a class level attribute from an invokable controller registered without a method', function () {
    Route::any('/invokable-class-level', ClassLevelCacheInvokableController::class)->middleware(CacheResponse::class);

    assertRegularResponse($this->get('/invokable-class-level'));
    assertCachedResponse($this->get('/invokable-class-level'));
});

it('reads a no cache attribute from an invokable controller registered without a method', function () {
    config()->set('responsecache.cache.lifetime_in_seconds', 300);

    Route::any('/invokable-no-cache', InvokableNoCacheController::class)->middleware(CacheResponse::class);

    assertRegularResponse($this->get('/invokable-no-cache'));
    assertRegularResponse($this->get('/invokable-no-cache'));
});

it('still reads attributes from an invokable controller registered with an explicit method', function () {
    Route::any('/invokable-explicit', [InvokableCacheController::class, '__invoke'])->middleware(CacheResponse::class);

    assertRegularResponse($this->get('/invokable-explicit'));
    assertCachedResponse($this->get('/invokable-explicit'));
});
