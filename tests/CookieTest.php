<?php

use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Middlewares\FlexibleCacheResponse;

beforeEach(function () {
    $setsCookie = fn () => response('page')->cookie('cart_id', 'cart-of-first-visitor', 0, null, null, false, false, false, null);

    Route::get('/sets-cookie', $setsCookie)->middleware(CacheResponse::class);
    Route::get('/flexible-sets-cookie', $setsCookie)->middleware(FlexibleCacheResponse::for(lifetime: 60, grace: 60));
});

it('does not replay cookies from a cached response', function (string $uri) {
    $firstResponse = $this->get($uri);
    $secondResponse = $this->get($uri);

    $firstResponse->assertPlainCookie('cart_id', 'cart-of-first-visitor');
    $secondResponse->assertCookieMissing('cart_id');
    expect($secondResponse->getContent())->toBe('page');
})->with(['/sets-cookie', '/flexible-sets-cookie']);

it('caches the response for the next visitor', function (string $uri) {
    $this->get($uri);

    assertCachedResponse($this->get($uri));
})->with(['/sets-cookie', '/flexible-sets-cookie']);
