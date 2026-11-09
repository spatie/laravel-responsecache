<?php

use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Attributes\NoCache;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Support\AttributeReader;
use Spatie\ResponseCache\Test\TestClasses\ClassLevelCacheInvokableController;
use Spatie\ResponseCache\Test\TestClasses\ContextualCacheController;
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

it('returns a fresh attribute instance for every lookup of the same action', function () {
    $attributeClasses = [Cache::class, FlexibleCache::class, NoCache::class];

    $first = AttributeReader::getFirstAttribute(InvokableCacheController::class, $attributeClasses);
    $first->lifetime = 1;

    $second = AttributeReader::getFirstAttribute(InvokableCacheController::class, $attributeClasses);

    expect($second)->not->toBe($first)
        ->and($second->lifetime)->toBe(300);
});

it('reads the current constructor context on every attribute lookup', function () {
    config()->set('responsecache.test_lifetime', 37);

    $first = AttributeReader::getFirstAttribute(ContextualCacheController::class, [Cache::class]);

    config()->set('responsecache.test_lifetime', 83);

    $second = AttributeReader::getFirstAttribute(ContextualCacheController::class, [Cache::class]);

    expect($first->lifetime)->toBe(37)
        ->and($second->lifetime)->toBe(83);
});

it('does not share nested attribute state between lookups', function () {
    $first = AttributeReader::getFirstAttribute(ContextualCacheController::class, [Cache::class]);
    $first->state->value = 'changed';

    $second = AttributeReader::getFirstAttribute(ContextualCacheController::class, [Cache::class]);

    expect($second->state->value)->toBe('original');
});

it('can read an attribute that cannot be cloned', function () {
    $action = ContextualCacheController::class.'@uncloneable';

    $first = AttributeReader::getFirstAttribute($action, [Cache::class]);
    $second = AttributeReader::getFirstAttribute($action, [Cache::class]);

    expect($second)->not->toBe($first)
        ->and($second->lifetime)->toBe(37);
});

it('can read attributes after a previously missing controller becomes available', function () {
    $action = 'LateLoadedCacheController';

    expect(AttributeReader::getFirstAttribute($action, [Cache::class]))->toBeNull();

    class_alias(InvokableCacheController::class, $action);

    expect(AttributeReader::getFirstAttribute($action, [Cache::class])->lifetime)->toBe(300);
});
