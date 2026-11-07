<?php

use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\CacheResponse;

beforeEach(function () {
    Route::middleware(['web', CacheResponse::class])->group(function () {
        Route::get('/etag-with-token', fn () => response('<input value="'.csrf_token().'">')->setEtag('original'));
        Route::get('/etag-static', fn () => response('<p>Same for everyone</p>')->setEtag('static'));
    });

    Route::middleware(['web', 'cache.headers:etag', CacheResponse::class])
        ->get('/computed-etag', fn () => '<input value="'.csrf_token().'">');
});

it('drops the etag of a cached response whose body a replacer changed', function () {
    $this->get('/etag-with-token')->assertHeader('ETag', '"original"');

    $cachedResponse = $this->get('/etag-with-token');

    assertCachedResponse($cachedResponse);
    $cachedResponse->assertHeaderMissing('ETag');
});

it('keeps the etag of a cached response that no replacer changed', function () {
    $this->get('/etag-static');

    $cachedResponse = $this->get('/etag-static');

    assertCachedResponse($cachedResponse);
    $cachedResponse->assertHeader('ETag', '"static"');
});

it('lets the cache headers middleware compute an etag for the body each visitor receives', function () {
    $this->get('/computed-etag');

    session()->regenerateToken();
    $cachedResponse = $this->get('/computed-etag');

    assertCachedResponse($cachedResponse);
    expect($cachedResponse->getContent())->toContain(csrf_token())
        ->and($cachedResponse->headers->get('ETag'))->toBe('"'.hash('xxh128', $cachedResponse->getContent()).'"');
});
