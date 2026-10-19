<?php

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Facades\ResponseCache;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Test\Middlewares\ChangeCacheNameSuffixAfterResponse;
use Spatie\ResponseCache\Test\Middlewares\ConvertToMarkdownResponse;
use Spatie\ResponseCache\Test\Middlewares\ConvertToRedirectResponse;
use Spatie\ResponseCache\Test\Middlewares\TrimResponseContent;

it('includes modifications made by RequestHandled listeners in the cached response', function () {
    $rendered = false;

    Route::get('/with-listener', function () use (&$rendered) {
        $rendered = true;

        return '<html><head></head><body>page content</body></html>';
    })->middleware(CacheResponse::class);

    app('events')->listen(RequestHandled::class, function ($handled) use (&$rendered) {
        if (! $rendered) {
            return;
        }

        $rendered = false;

        $content = $handled->response->getContent();
        $handled->response->setContent(
            str_replace('</head>', '<script src="livewire.js"></script></head>', $content)
        );
    });

    $firstResponse = $this->get('/with-listener');
    assertRegularResponse($firstResponse);
    expect($firstResponse->getContent())->toContain('<script src="livewire.js"></script>');

    $secondResponse = $this->get('/with-listener');
    assertCachedResponse($secondResponse);
    expect($secondResponse->getContent())->toContain('<script src="livewire.js"></script>');

    assertSameResponse($firstResponse, $secondResponse);
});

it('caches the response even when the middleware is resolved as a fresh instance in terminate', function () {
    // Override the singleton binding so handle() and terminate() receive different middleware instances.
    app()->bind(CacheResponse::class);

    Route::get('/non-singleton', fn () => 'fresh content')
        ->middleware(CacheResponse::class);

    $firstResponse = $this->get('/non-singleton');
    assertRegularResponse($firstResponse);

    $secondResponse = $this->get('/non-singleton');
    assertCachedResponse($secondResponse);
    assertSameResponse($firstResponse, $secondResponse);
});

it('does not cache a response that an outer middleware replaced with a different content type', function () {
    Route::get('/markdown', fn () => '<html><body>page content</body></html>')
        ->middleware([ConvertToMarkdownResponse::class, CacheResponse::class]);

    $markdownResponse = $this->get('/markdown', ['Accept' => 'text/markdown']);
    expect($markdownResponse->headers->get('Content-Type'))->toStartWith('text/markdown');

    $htmlResponse = $this->get('/markdown');
    assertRegularResponse($htmlResponse);
    expect($htmlResponse->headers->get('Content-Type'))->toStartWith('text/html');

    $cachedHtmlResponse = $this->get('/markdown');
    assertCachedResponse($cachedHtmlResponse);
    expect($cachedHtmlResponse->headers->get('Content-Type'))->toStartWith('text/html');
});

it('does not cache a response whose status code was changed by an outer middleware', function () {
    Route::get('/redirected', fn () => 'page content')
        ->middleware([ConvertToRedirectResponse::class, CacheResponse::class]);

    $this->get('/redirected')->assertRedirect('/accept-terms');

    expect(ResponseCache::hasBeenCached(Request::create('/redirected')))->toBeFalse();
});

it('caches a response that an outer middleware replaced with one of the same content type', function () {
    Route::get('/trimmed', fn () => '   page content   ')
        ->middleware([TrimResponseContent::class, CacheResponse::class]);

    $firstResponse = $this->get('/trimmed');
    assertRegularResponse($firstResponse);

    $secondResponse = $this->get('/trimmed');
    assertCachedResponse($secondResponse);
    expect($secondResponse->getContent())->toBe('page content');
});

it('stores the response under the cache key computed while handling the request', function () {
    Route::get('/changed-suffix', fn () => 'page content')
        ->middleware([ChangeCacheNameSuffixAfterResponse::class, CacheResponse::class]);

    $firstResponse = $this->get('/changed-suffix');
    assertRegularResponse($firstResponse);

    $secondResponse = $this->get('/changed-suffix');
    assertCachedResponse($secondResponse);
});

it('caches the response when the pending cache attribute only contains the lifetime and tags', function () {
    $request = Request::create('/legacy-pending');
    $request->attributes->set('_response_cache.pending', [
        'lifetime' => null,
        'tags' => [],
    ]);

    app(CacheResponse::class)->terminate($request, response('page content', 200, ['Content-Type' => 'text/html']));

    expect(ResponseCache::hasBeenCached(Request::create('/legacy-pending')))->toBeTrue();
});
