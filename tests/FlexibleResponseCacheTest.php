<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Spatie\ResponseCache\Events\CacheMissed;
use Spatie\ResponseCache\Events\ResponseCacheHit;

beforeEach(function () {
    config()->set('responsecache.cache_store', 'array');
    config()->set('cache.default', 'array');
    Cache::forgetDriver('array');

    Carbon::setTestNow(Carbon::now());
});

afterEach(function () {
    Carbon::setTestNow();
});

it('returns cached response within fresh period', function () {
    $firstResponse = $this->get('/flexible/basic');
    $firstContent = $firstResponse->getContent();

    Carbon::setTestNow(Carbon::now()->addSeconds(3));

    $secondResponse = $this->get('/flexible/basic');
    $secondContent = $secondResponse->getContent();

    expect($secondContent)->toBe($firstContent);
});

it('returns cached response within fresh period using carbon functions in flexible method', function () {
    $firstResponse = $this->get('/flexible/carbon-interval');
    $firstContent = $firstResponse->getContent();

    Carbon::setTestNow(Carbon::now()->addSeconds(3));

    $secondResponse = $this->get('/flexible/carbon-interval');
    $secondContent = $secondResponse->getContent();

    expect($secondContent)->toBe($firstContent);
});


it('adds cache time header when configured', function () {
    config()->set('responsecache.add_cache_time_header', true);
    config()->set('responsecache.cache_time_header_name', 'X-Cached-At');

    $this->get('/flexible/basic');

    $cachedResponse = $this->get('/flexible/basic');

    expect($cachedResponse->headers->has('X-Cached-At'))->toBeTrue();
});

it('adds cache age header when configured', function () {
    config()->set('responsecache.add_cache_time_header', true);
    config()->set('responsecache.add_cache_age_header', true);
    config()->set('responsecache.cache_age_header_name', 'X-Cache-Age');

    $this->get('/flexible/basic');

    Carbon::setTestNow(Carbon::now()->addSeconds(2));

    $cachedResponse = $this->get('/flexible/basic');

    expect($cachedResponse->headers->has('X-Cache-Age'))->toBeTrue();
    expect((int) $cachedResponse->headers->get('X-Cache-Age'))->toBeGreaterThanOrEqual(0);
});

it('returns stale response immediately when in stale window', function () {
    $firstResponse = $this->get('/flexible/basic');
    $firstContent = $firstResponse->getContent();

    Carbon::setTestNow(Carbon::now()->addSeconds(7));

    $secondResponse = $this->get('/flexible/basic');
    $secondContent = $secondResponse->getContent();

    expect($secondContent)->toBe($firstContent);
});

it('schedules background refresh during stale period', function () {
    $firstResponse = $this->get('/flexible/basic');
    $firstContent = $firstResponse->getContent();

    Carbon::setTestNow(Carbon::now()->addSeconds(7));

    $secondResponse = $this->get('/flexible/basic');
    $secondContent = $secondResponse->getContent();

    expect($secondContent)->toBe($firstContent);

    app()->terminate();

    $thirdResponse = $this->get('/flexible/basic');
    $thirdContent = $thirdResponse->getContent();

    expect($thirdContent)->not->toBe($firstContent);
});

it('returns stale content on first request in stale window', function () {
    $firstResponse = $this->get('/flexible/basic');
    $firstContent = $firstResponse->getContent();

    Carbon::setTestNow(Carbon::now()->addSeconds(7));

    $secondResponse = $this->get('/flexible/basic');
    expect($secondResponse->getContent())->toBe($firstContent);

    $thirdResponse = $this->get('/flexible/basic');
    expect($thirdResponse->getContent())->not->toBe($firstContent);
});

it('recomputes response when beyond stale window', function () {
    $firstResponse = $this->get('/flexible/basic');
    $firstContent = $firstResponse->getContent();

    Carbon::setTestNow(Carbon::now()->addSeconds(16));

    $secondResponse = $this->get('/flexible/basic');
    $secondContent = $secondResponse->getContent();

    expect($secondContent)->not->toBe($firstContent);
    expect($secondContent)->toStartWith('random-');
});

it('blocks during recomputation when cache is expired', function () {
    $firstResponse = $this->get('/flexible/basic');
    $firstContent = $firstResponse->getContent();

    Carbon::setTestNow(Carbon::now()->addSeconds(20));

    $secondResponse = $this->get('/flexible/basic');

    $thirdResponse = $this->get('/flexible/basic');

    expect($secondResponse->getContent())->not->toBe($firstContent);
    expect($thirdResponse->getContent())->toBe($secondResponse->getContent());
});


it('respects custom fresh and stale periods', function () {
    $firstResponse = $this->get('/flexible/custom-time');
    $firstContent = $firstResponse->getContent();

    Carbon::setTestNow(Carbon::now()->addSeconds(2));
    $freshResponse = $this->get('/flexible/custom-time');
    expect($freshResponse->getContent())->toBe($firstContent);

    Carbon::setTestNow(Carbon::now()->addSeconds(8));
    $staleResponse = $this->get('/flexible/custom-time');
    expect($staleResponse->getContent())->toBe($firstContent);

    app()->terminate();

    $refreshedResponse = $this->get('/flexible/custom-time');
    expect($refreshedResponse->getContent())->not->toBe($firstContent);
});

it('stores flexible cache with tags', function () {
    $firstResponse = $this->get('/flexible/with-tags');
    $secondResponse = $this->get('/flexible/with-tags');

    expect($secondResponse->getContent())->toBe($firstResponse->getContent());
});

it('can clear tagged flexible cache', function () {
    $firstResponse = $this->get('/flexible/with-tags');
    $firstContent = $firstResponse->getContent();

    Cache::tags(['tag1'])->flush();

    $secondResponse = $this->get('/flexible/with-tags');
    $secondContent = $secondResponse->getContent();

    expect($secondContent)->not->toBe($firstContent);
});

it('schedules background refresh with tags during stale period', function () {
    $firstResponse = $this->get('/flexible/with-tags');
    $firstContent = $firstResponse->getContent();

    Carbon::setTestNow(Carbon::now()->addSeconds(7));

    $staleResponse = $this->get('/flexible/with-tags');
    expect($staleResponse->getContent())->toBe($firstContent);

    app()->terminate();

    $refreshedResponse = $this->get('/flexible/with-tags');
    expect($refreshedResponse->getContent())->not->toBe($firstContent);
});

it('fires ResponseCacheHit event when serving from cache', function () {
    Event::fake();

    $this->get('/flexible/basic');
    $this->get('/flexible/basic');

    Event::assertDispatched(ResponseCacheHit::class);
})->skip('Currently this test does not work due to a bug in Laravel 11');

it('fires CacheMissed event on first request', function () {
    Event::fake();

    $this->get('/flexible/basic');

    Event::assertDispatched(CacheMissed::class);
})->skip('Currently this test does not work due to a bug in Laravel 11');


it('parses flexible time format correctly', function () {
    $firstResponse = $this->get('/flexible/basic');
    $secondResponse = $this->get('/flexible/basic');

    expect($secondResponse->getContent())->toBe($firstResponse->getContent());
});

it('parses flexible time with tags correctly', function () {
    $firstResponse = $this->get('/flexible/with-tags');
    $firstContent = $firstResponse->getContent();

    $secondResponse = $this->get('/flexible/with-tags');
    expect($secondResponse->getContent())->toBe($firstContent);

    Cache::tags(['tag1'])->flush();

    $thirdResponse = $this->get('/flexible/with-tags');
    expect($thirdResponse->getContent())->not->toBe($firstContent);
});

it('bypasses flexible cache when bypass header is present', function () {
    config()->set('responsecache.cache_bypass_header.name', 'X-Cache-Bypass');
    config()->set('responsecache.cache_bypass_header.value', 'true');

    $firstResponse = $this->get('/flexible/basic', ['X-Cache-Bypass' => 'true']);
    $secondResponse = $this->get('/flexible/basic', ['X-Cache-Bypass' => 'true']);

    expect($secondResponse->getContent())->not->toBe($firstResponse->getContent());
});

it('does not use flexible cache when package is disabled', function () {
    config()->set('responsecache.enabled', false);

    $firstResponse = $this->get('/flexible/basic');
    $secondResponse = $this->get('/flexible/basic');

    expect($secondResponse->getContent())->not->toBe($firstResponse->getContent());
});

it('handles concurrent requests during cache miss', function () {
    $firstResponse = $this->get('/flexible/basic');

    $responses = [];
    for ($i = 0; $i < 5; $i++) {
        $responses[] = $this->get('/flexible/basic');
    }

    foreach ($responses as $response) {
        expect($response->getContent())->toBe($firstResponse->getContent());
    }
});

it('maintains cache independence across different routes', function () {
    $responseBasic1 = $this->get('/flexible/basic');
    $responseCustom1 = $this->get('/flexible/custom-time');

    $responseBasic2 = $this->get('/flexible/basic');
    $responseCustom2 = $this->get('/flexible/custom-time');

    expect($responseBasic2->getContent())->toBe($responseBasic1->getContent());
    expect($responseCustom2->getContent())->toBe($responseCustom1->getContent());
    expect($responseBasic1->getContent())->not->toBe($responseCustom1->getContent());
});
