<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Middlewares\FlexibleCacheResponse;
use Spatie\ResponseCache\Replacers\Replacer;
use Symfony\Component\HttpFoundation\Response;

class QueueVisitorCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        Cookie::queue('visitor', $request->header('X-Visitor'));

        return $next($request);
    }
}

class ReplaceVisitorCookie implements Replacer
{
    public function prepareResponseToCache(Response $response): void {}

    public function replaceInCachedResponse(Response $response): void
    {
        $response->headers->setCookie(cookie('cart_id', request()->header('X-Visitor')));
    }
}

dataset('response cache middleware', [
    'regular' => CacheResponse::class,
    'flexible' => FlexibleCacheResponse::for(lifetime: 60, grace: 60),
]);

it('keeps the original cookies without storing or replaying them', function (string $middleware, bool $web) {
    Route::get('/cookie-headers', fn () => response('page')->cookie('cart_id', 'visitor-a'))
        ->middleware($web ? ['web', $middleware] : [$middleware]);

    $firstResponse = $this->get('/cookie-headers');

    $web
        ? $firstResponse->assertCookie('cart_id', 'visitor-a')
        : $firstResponse->assertPlainCookie('cart_id', 'visitor-a');

    $storedResponse = app('responsecache')->getCachedResponseFor(Request::create('http://localhost/cookie-headers'));

    expect($storedResponse->headers->getCookies())->toBeEmpty();

    $cachedResponse = $this->withCookie('cart_id', 'visitor-b')->get('/cookie-headers');

    assertCachedResponse($cachedResponse);
    $cachedResponse->assertCookieMissing('cart_id')->assertSee('page');

    if ($middleware !== CacheResponse::class) {
        $this->travel(61)->seconds();

        $this->get('/cookie-headers')->assertCookieMissing('cart_id')->assertSee('page');
        $this->get('/cookie-headers')->assertCookieMissing('cart_id')->assertSee('page');
    }
})->with('response cache middleware')->with(['without web' => false, 'with web' => true]);

it('keeps cookies queued by outer middleware for the current visitor', function (string $middleware) {
    Route::get('/queued-cookie', fn () => 'page')->middleware(['web', QueueVisitorCookie::class, $middleware]);

    $this->get('/queued-cookie', ['X-Visitor' => 'visitor-a'])->assertCookie('visitor', 'visitor-a');
    Cookie::flushQueuedCookies();

    $cachedResponse = $this->get('/queued-cookie', ['X-Visitor' => 'visitor-b']);

    assertCachedResponse($cachedResponse);
    $cachedResponse->assertCookie('visitor', 'visitor-b');
})->with('response cache middleware');

it('keeps cookies added by a replacer for the current visitor', function (string $middleware) {
    config()->set('responsecache.replacers', [ReplaceVisitorCookie::class]);

    Route::get('/replacer-cookie', fn () => response('page')->cookie('cart_id', 'visitor-a'))
        ->middleware($middleware);

    $this->get('/replacer-cookie', ['X-Visitor' => 'visitor-a']);

    $cachedResponse = $this->get('/replacer-cookie', ['X-Visitor' => 'visitor-b']);

    assertCachedResponse($cachedResponse);
    $cachedResponse->assertPlainCookie('cart_id', 'visitor-b');
})->with('response cache middleware');

it('sanitizes cookies and validators from existing cache entries', function (string $middleware, bool $hasToken) {
    $request = Request::create('http://localhost/legacy-headers');
    $body = $hasToken ? '<input value="<laravel-responsecache-csrf-token-here>">' : 'public cached page';
    $legacyResponse = response($body)
        ->cookie('cart_id', 'visitor-a')
        ->setEtag('legacy-version');

    $middleware === CacheResponse::class
        ? app('responsecache')->cacheResponse($request, $legacyResponse)
        : app('responsecache')->flexible(app(RequestHasher::class)->getHashFor($request), [60, 120], fn () => $legacyResponse);

    Route::get('/legacy-headers', fn () => abort(500, 'The existing entry should be served'))
        ->middleware(['web', 'cache.headers:etag', $middleware]);

    session()->regenerateToken();

    $cachedResponse = $this->get('/legacy-headers', $hasToken ? ['If-None-Match' => '"legacy-version"'] : []);

    $expectedBody = $hasToken ? '<input value="'.csrf_token().'">' : $body;
    $expectedEtag = $hasToken ? '"'.hash('xxh128', $expectedBody).'"' : '"legacy-version"';

    $cachedResponse->assertOk()->assertCookieMissing('cart_id');
    expect($cachedResponse->getContent())->toBe($expectedBody)
        ->and($cachedResponse->getEtag())->toBe($expectedEtag);
})->with('response cache middleware')->with(['static body' => false, 'token body' => true]);

it('invalidates etags when replacers change a response body', function (string $middleware) {
    Route::get('/token-headers', function () {
        $body = '<input value="'.csrf_token().'">';

        return response($body)->setEtag(hash('xxh128', $body));
    })->middleware(['web', 'cache.headers:etag', $middleware]);

    session()->regenerateToken();
    $firstResponse = $this->get('/token-headers');
    $firstResponse->assertHeader('ETag', '"'.hash('xxh128', $firstResponse->getContent()).'"');

    $storedResponse = app('responsecache')->getCachedResponseFor(Request::create('http://localhost/token-headers'));
    expect($storedResponse->headers->has('ETag'))->toBeFalse();

    session()->regenerateToken();
    $cachedResponse = $this->get('/token-headers', ['If-None-Match' => $firstResponse->getEtag()]);

    assertCachedResponse($cachedResponse);
    $cachedResponse->assertOk();
    expect($cachedResponse->getContent())->toBe('<input value="'.csrf_token().'">')
        ->not->toBe($firstResponse->getContent())
        ->and($cachedResponse->getEtag())->toBe('"'.hash('xxh128', $cachedResponse->getContent()).'"');
})->with('response cache middleware');

it('preserves etags and conditional requests when replacers do not change the body', function (string $middleware) {
    Route::get('/static-headers', fn () => response('public page')->setEtag('public-version'))
        ->middleware(['web', 'cache.headers:etag', $middleware]);

    $this->get('/static-headers')->assertHeader('ETag', '"public-version"');

    $cachedResponse = $this->get('/static-headers');
    assertCachedResponse($cachedResponse);
    $cachedResponse->assertHeader('ETag', '"public-version"')->assertSee('public page');

    $this->get('/static-headers', ['If-None-Match' => '"public-version"'])->assertStatus(304);
})->with('response cache middleware');
