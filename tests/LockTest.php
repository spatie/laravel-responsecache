<?php

use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\ResponseCache\Middlewares\CacheResponse;

function lockNameFor(string $uri, string $method = 'GET'): string
{
    return 'responsecache-lock-'.app('responsecache')->cacheKeyFor(Request::create("http://localhost{$uri}", $method));
}

function lockStore()
{
    return app('cache')->store(config('responsecache.cache.store'))->getStore();
}

beforeEach(function () {
    config()->set('responsecache.lock.enabled', true);
    config()->set('responsecache.lock.wait_in_seconds', 1);

    $this->renders = 0;

    Route::any('/locked', function () {
        $this->renders++;

        return 'rendered '.Str::random();
    })->middleware(CacheResponse::class);

    Route::get('/locked-failing', fn () => throw new RuntimeException('boom'))->middleware(CacheResponse::class);
    Route::get('/locked-not-found', fn () => abort(404))->middleware(CacheResponse::class);
});

it('serves the response that another request cached while it waited for the lock', function () {
    $filled = false;

    Event::listen(CacheMissed::class, function () use (&$filled) {
        if ($filled) {
            return;
        }

        $filled = true;
        app('responsecache')->cacheResponse(Request::create('http://localhost/locked'), response('rendered by another request'));
    });

    $response = $this->get('/locked');

    expect($response->getContent())->toBe('rendered by another request')
        ->and($this->renders)->toBe(0);
});

it('renders the page itself when the lock cannot be acquired in time', function () {
    config()->set('responsecache.lock.wait_in_seconds', 0);
    lockStore()->lock(lockNameFor('/locked'), 10, 'another-request')->acquire();

    $response = $this->get('/locked');

    expect($response->getContent())->toStartWith('rendered ')
        ->and($this->renders)->toBe(1);
});

it('releases the lock once the response is cached', function () {
    $this->get('/locked');

    assertCachedResponse($this->get('/locked'));
    expect(lockStore()->lock(lockNameFor('/locked'), 10)->get())->toBeTrue();
});

it('releases the lock when the controller throws', function () {
    $this->get('/locked-failing')->assertServerError();

    expect(lockStore()->lock(lockNameFor('/locked-failing'), 10)->get())->toBeTrue();
});

it('releases the lock when an exception passes through the middleware', function () {
    $request = Request::create('http://localhost/unrouted');

    expect(fn () => app(CacheResponse::class)->handle($request, fn () => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class);

    expect(lockStore()->lock(lockNameFor('/unrouted'), 10)->get())->toBeTrue();
});

it('releases the lock when the response is not cacheable', function () {
    $this->get('/locked-not-found')->assertNotFound();

    expect(lockStore()->lock(lockNameFor('/locked-not-found'), 10)->get())->toBeTrue();
});

it('does not lock requests that will never be cached', function () {
    lockStore()->lock(lockNameFor('/locked', 'POST'), 10, 'another-request')->acquire();
    config()->set('responsecache.lock.wait_in_seconds', 5);

    $startedAt = microtime(true);
    $this->post('/locked');

    expect(microtime(true) - $startedAt)->toBeLessThan(1.0)
        ->and($this->renders)->toBe(1);
});

it('does not lock when the lock is disabled', function () {
    config()->set('responsecache.lock.enabled', false);
    config()->set('responsecache.lock.wait_in_seconds', 5);
    lockStore()->lock(lockNameFor('/locked'), 10, 'another-request')->acquire();

    $startedAt = microtime(true);
    $this->get('/locked');

    expect(microtime(true) - $startedAt)->toBeLessThan(1.0)
        ->and($this->renders)->toBe(1);
});
